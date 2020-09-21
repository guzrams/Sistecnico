# -*- coding: utf-8 -*-

from flask import Flask, render_template, request, redirect, url_for, session, jsonify, flash
from flask_login import LoginManager, login_user, logout_user, login_required, UserMixin, current_user
from flask_bcrypt import Bcrypt
import re
import os
import pathlib
import datetime
from werkzeug.utils import secure_filename
from PIL import Image
from resizeimage import resizeimage

import dbcn
import usuarios
import permisos as permisosU
import asientos as asi
import documentos as docu
import documentos_pdf as dpdf
import tipodocs as tdoc
import geo as geo
import img
import loc_img

'''
import paises
import departamentos
import mundo as mun
import provincias as prov
import seccion as secc
import localidad as locc
import circunscripcion as ccircun
import tipolocloc as locloc
import historicolocloc2
'''

# create the application object
app = Flask(__name__)
app.secret_key ='\xfd{H\xe7<\x95\xf9\xe3\x96.5\xd1\x01O<!\xd5\xa2\xa0\x9fR"\xa1\xa7'
app.config['LOGIN_DISABLED'] = False
app.config['IMG_ASIENTOS'] = '/static/imgbd/asi'
app.config['IMG_RECINTOS'] = '/static/imgbd/reci'
app.config['SUBIR_PDF'] = '/static/pdfdoc'

ALLOWED_EXTENSIONS = set(['pdf'])

BCRYPT_LOG_ROUNDS = 15
bcrypt = Bcrypt(app)

login_manager = LoginManager()
login_manager.init_app(app)
login_manager.login_view = ''

# globales
usr = ""
usrdep = 99
usrid = 0
permisos_usr = []

def allowed_file(filename):
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS


@app.before_request
def before_request_func():

    """
    This function will run before every request. Let's add something to the session & g.
    It's a good place for things like establishing database connections, retrieving
    """

    global cxms
    global cxpg
    cxms = dbcn.get_db_ms()
    cxpg = dbcn.get_db_pg()


@app.teardown_request
def teardown_request_func(error=None):

    """
    This function will run after a request, regardless if an exception occurs or not.
    It's a good place to do some cleanup, such as closing any database connections.
    """

    cxpg.close()
    cxms.close()

    if error:
        # Log the error
        print(str(error))


@login_manager.user_loader
def user_loader(txtusr):
    global usr
    global usrdep
    global usrid
    global permisos_usr
    user = usuarios.Usuarios(cxms)

    if user.get_usuario(txtusr):
        usr = user.usuario
        usrdep = user.dep
        usrid = user.id
        permisos_usr = user.get_permisos_name(usr)
        return user


@app.context_processor
def utility_processor():
    def current_date_format(date):
        months = ("01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12")
        day = date.day
        month = months[date.month - 1]
        year = date.year
        hora = date.strftime('%H:%M:%S')
        messsage = "{}-{}-{} {}".format(day, month, year, hora)
        return messsage
    return dict(fecha=current_date_format)


@app.context_processor
def inject_global():
    print(str(datetime.datetime.now())[0:-3])
    return dict(idate=datetime.date.today(), idatetime=str(datetime.datetime.now())[0:-3], usuario=usr, usrdep=usrdep, usrid=usrid)


@app.errorhandler(401)
def access_error(error):
    return render_template('401.html'), 401


@app.route('/get_geo', methods=['GET', 'POST'])
def get_geo():
    lat = request.args.get('latitud', 0, type=float)
    long = request.args.get('longitud', 0, type=float)

    g = geo.LatLong(cxpg)
    if g.get_geo(lat, long):
        return jsonify(dep=g.dep,
                       departamento=g.departamento,
                       prov=g.prov,
                       provincia=g.provincia,
                       sec=g.sec,
                       municipio=g.municipio)
    else:
        return jsonify(dep='---',
                       departamento='COORDENADA',
                       prov='---',
                       provincia='INCORRECTA !!!',
                       sec='---',
                       municipio='INTENTE NUEVAMENTE....')

@app.route('/')
def home():
    return render_template('home.html')


@app.route('/login', methods=['GET', 'POST'])
def login():

    u = usuarios.Usuarios(cxms)

    error = None
    if request.method == 'POST':

        if u.get_usuario(request.form['uname']):
            pw_es_igual = bcrypt.check_password_hash(u.password, request.form['pswd'])

            if request.form['uname'] != u.usuario  or not pw_es_igual:
                error = 'Credencial Inválida. Por favor intente nuevamente.'
            else:
                if login_user(u):
                    print ('Login OK')
                else:
                    print ('Login Error')

                return redirect(url_for('habilitado'))
        else:
            error = 'Usuario no registrado previamente'

    return render_template('login.html', error=error)


@app.route('/change_pwd', methods=['GET', 'POST'])
@login_required
def change_pwd():
    u = usuarios.Usuarios(cxms)
    error = None

    if request.method == 'POST':
        if u.get_usuario_usr(usr):
            pw_es_igual = bcrypt.check_password_hash(u.password, request.form['pwdold'])
            if not pw_es_igual:   # valida usr
                error = "Password anterior NO coincide...!"
                return render_template('pwd.html', error=error, u=u, load_u=True)
            elif request.form['pwdnew'] != request.form['pwdnew2'] :
                error = "Error en password nuevo NO coincide con password confirmado...!"
                return render_template('pwd.html', error=error, u=u, load_u=True)
            else:
                pw_hash = bcrypt.generate_password_hash(request.form['pwdnew']).decode('UTF-8')
                u.upd_pwd_usuario(usr, \
                            pw_hash, \
                            )
                return render_template('welcome.html')

    return render_template('pwd.html', error=error, u=u, load_u=False)


@app.route('/registro/<usuario_id>', methods=['GET', 'POST'])
def registro(usuario_id=None):
    u = usuarios.Usuarios(cxms)
    error = None

    if request.method == 'POST':
        if usuario_id == '0':  # es NEW
            if u.get_usuario(request.form['uname']) == True:   # valida usr
                error = "El usuario: " + request.form['uname']  + " ya existe...!"
                return render_template('registro.html', error=error, u=u, load_u=True)
            else:
                pw_hash = bcrypt.generate_password_hash(request.form['pswd']).decode('UTF-8')
                u.add_usuario(request.form['uname'], \
                            request.form['nombre'], \
                            request.form['apellidos'], \
                            request.form['email'], \
                            pw_hash, \
                            request.form['dep'], \
                            1)
                return render_template('welcome.html')
        else: # es EDIT
            u.upd_usuario(usuario_id, \
                            request.form['nombre'], \
                            request.form['apellidos'], \
                            request.form['email'], \
                            request.form['dep'], \
                            1)
            if usr == 'admin':
                return render_template('usuarios.html', usuarios=u.get_usuarios())
            return render_template('home.html')

    else: # viene de listado USUARIOS
        if usuario_id != 0:  # EDIT
            if u.get_usuario_id(usuario_id) == True:
                return render_template('registro.html', error=error, u=u, load_u=True)

    return render_template('registro.html', error=error, u=u, load_u=False)


@app.route('/m_usuarios', methods=['GET', 'POST'])
@login_required
def m_usuarios():
    u = usuarios.Usuarios(cxms)
    rows = u.get_usuarios()
    if rows:
        return render_template('usuarios.html', usuarios=rows)
    else:
        print ('Sin usuarios...')


@app.route('/usuario_del/<usuario_id>', methods=['GET', 'POST'])
@login_required
def usuario_del(usuario_id):
    u = usuarios.Usuarios(cxms)
    u.del_usuario(usuario_id)

    rows = u.get_usuarios()
    if rows:
        return render_template('usuarios.html', usuarios=rows)
    else:
        print ('Sin usuarios...')


@app.route('/permisos/<usuario_id>', methods=['GET', 'POST'])
@login_required
def permisos(usuario_id):
    u = usuarios.Usuarios(cxms)
    p = permisosU.Permisos(cxms)

    if request.method == 'POST':
        # Grabando
        vl = request.form['values_li']
        if vl != 'salir':
            p.reset_permisos_de_usuario(usuario_id)
            p.save_permisos_txt(usuario_id, request.form['values_li'])

        rows = u.get_usuarios()
        if rows:
            return render_template('usuarios.html', usuarios=rows)
        else:
            print ('Sin usuarios...')
    else:
        if u.get_usuario_id(usuario_id):
            m_rows = p.get_modulos_sin_asignar(usuario_id)      # False or rows
            pu_rows =  p.get_permisos_de_usuario(usuario_id)  # False or rows
            return render_template('permisos.html', usuario=u, modulos=m_rows, permisos_usuario=pu_rows)


@app.route('/asiento_img/<idloc>/<string:nomloc>', methods=['GET', 'POST'])
@login_required
def asiento_img(idloc, nomloc):

    i = img.Img(cxms)  # conecta a la BD
    li = loc_img.LocImg(cxms)

    with_img = li.get_loc_imgs(idloc)  # False or rows-img

    error = None

    if request.method == 'POST':
        img_ids_ = request.form.getlist('imgsa[]')  # options img for Asiento
        img_ids = list(img_ids_[0].split(","))      # list ok

        uploaded_files = request.files.getlist("filelist")

        for n in range(len(img_ids)):
            f  = uploaded_files[n]
            if f.filename != '':
                securef = secure_filename(f.filename)
                f.save(os.path.join('.' + app.config['IMG_ASIENTOS'], securef))                
                fpath = os.path.join(app.config['IMG_ASIENTOS'], securef)
                arch, ext = os.path.splitext(fpath)
                name_to_save = str(idloc).zfill(5) + "_" + str(img_ids[n]).zfill(2)  + ext
                fpath_destino = os.path.join(app.config['IMG_ASIENTOS'], name_to_save)                
                resize_save_file(fpath, name_to_save, (1024, 768))
                li.add_loc_img(idloc, img_ids[n], fpath_destino, datetime.datetime.now(), usr)
                os.remove(fpath[1:])   # arch. fuente

        return redirect(url_for('asientos_list'))

    else:
        if with_img:  # Edit
            return render_template('asiento_img_upd.html', rows=i.get_imgs('Asiento'), nomloc=nomloc,
                                puede_editar='Asientos - Edición' in permisos_usr,
                                imgs_loaded=with_img)
        else:  # New
            return render_template('asiento_img.html', rows=i.get_imgs('Asiento'), nomloc=nomloc,
                                puede_editar='Asientos - Edición' in permisos_usr)

def resize_save_file(in_file, out_file, size):
    with open('.' + in_file, 'rb') as fd:
        image = resizeimage.resize_thumbnail(Image.open(fd), size)

    image.save('.' + os.path.join(app.config['IMG_ASIENTOS'], out_file))
    image.close()
    #return(out_file)

#Codigo Grover-Inicio
@app.route('/documentos_list', methods=['GET', 'POST'])
@login_required
def documentos_list():
    d = docu.Documentos(cxms)
    rows = d.get_documentos_all(usrdep)
    if rows:
        if permisos_usr:    # tiene pemisos asignados            
            return render_template('documentos_list.html', documentos=rows, puede_adicionar='Documentos - Adición' in permisos_usr)  # render a template
        else:
            return render_template('msg.html', l1='Sin permisos asignados !!')
    else:
        print ('Sin Documentos...')              

@app.route('/documento/<doc_id>', methods=['GET', 'POST'])
def documento(doc_id):    
    d = docu.Documentos(cxms)
    tdocu = tdoc.TipoDocs(cxms)
    error = None

    if request.method == 'POST':        
        if doc_id == '0':  # es NEW            
            nextid = d.get_next_id_doc()
            tipo = d.tipo_doc(request.form['doc'])
            tipo = tipo.lower()
            f = request.files['archivo']
            if allowed_file(f.filename):                
                filename = secure_filename(f.filename)
                filename = doc_id + '_' + filename        
                f.save(os.path.join('.' + app.config['SUBIR_PDF'], filename))
                fpath = os.path.join(app.config['SUBIR_PDF'], filename)
                fpath1 = os.path.join('.' + app.config['SUBIR_PDF'] + '/')
                arch, ext = os.path.splitext(fpath)
                name_to_save = str(nextid) + "_" + str(tipo) + ext            
                ruta = app.config['SUBIR_PDF'] + '/' + name_to_save
                os.rename(fpath1 + filename, fpath1 + name_to_save)
            else:
                flash('Debe cargar solo archivos PDFs')
                return render_template('documento.html', error=error, d=d, load_d=False, titulo='Registro de Documentos', tdocumentos=tdocu.get_tipo_documentos(usrdep))          
            d.add_documento(request.form['doc'], \
                        request.form['dep'], \
                        request.form['cite'], \
                        ruta, \
                        request.form['fechadoc'], \
                        request.form['obs'], \
                        request.form['fecharegistro'], \
                        request.form['usuario'], \
                        request.form['fechaingreso'])            
            return render_template('documentos_list.html', documentos=d.get_documentos_all(usrdep), puede_adicionar='Documentos - Adición' in permisos_usr)
        else: # es EDIT
            f = request.files['archivo']
            if allowed_file(f.filename):                            
                tipodo = d.tipo_doc(request.form['tipodocu'])
                tipodo = doc_id + "_" + tipodo + '.pdf'
                tipodo = tipodo.lower()                   
                ejemplo_dir = os.path.join('.' + app.config['SUBIR_PDF'] + '/')
                directorio = pathlib.Path(ejemplo_dir)
                for fichero in directorio.iterdir():
                    if fichero.name == tipodo:
                            os.remove(ejemplo_dir + fichero.name)
                
                tipo = d.tipo_doc(request.form['doc'])
                tipo = tipo.lower()
                f = request.files['archivo']
                filename = secure_filename(f.filename)
                filename = doc_id + '_' + filename        
                f.save(os.path.join('.' + app.config['SUBIR_PDF'], filename))
                fpath = os.path.join(app.config['SUBIR_PDF'], filename)
                fpath1 = os.path.join('.' + app.config['SUBIR_PDF'] + '/')
                arch, ext = os.path.splitext(fpath)
                name_to_save = doc_id + "_" + str(tipo) + ext            
                ruta = app.config['SUBIR_PDF'] + '/' + name_to_save
                os.rename(fpath1 + filename, fpath1 + name_to_save)
            else:                
                tipo = d.tipo_doc(request.form['doc'])
                tipo = tipo.lower()
                name_to_save = doc_id + "_" + str(tipo) + '.pdf'
                tipodo = d.tipo_doc(request.form['tipodocu'])
                tipodo = tipodo.lower()
                name_to_save1 = doc_id + "_" + str(tipodo) + '.pdf'            
                ruta = app.config['SUBIR_PDF'] + '/' + name_to_save
                ejemplo_dir = os.path.join('.' + app.config['SUBIR_PDF'] + '/')
                directorio = pathlib.Path(ejemplo_dir)
                for fichero in directorio.iterdir():
                    if fichero.name == name_to_save1:
                            os.rename(ejemplo_dir + fichero.name, ejemplo_dir + name_to_save)

            fa = str(datetime.datetime.now())[:-7]                            
            d.upd_documento(doc_id, \
                        request.form['doc'], \
                        request.form['dep'], \
                        request.form['cite'], \
                        ruta, \
                        request.form['fechadoc'], \
                        request.form['obs'], \
                        request.form['usuario'], \
                        fa)
            if usr == 'admin':
                return render_template('documentos_list.html', documentos=d.get_documentos())
            return render_template('documentos_list.html', documentos=d.get_documentos_all(usrdep), puede_adicionar='Documentos - Adición' in permisos_usr)

    else: # viene de listado DOCUMENTOS            
        if doc_id != 0:  # EDIT            
            if d.get_documento_id(doc_id) == True:                
                return render_template('documento.html', error=error, d=d, load_d=True, titulo='Modificacion de Documentos', tdocumentos=tdocu.get_tipo_documentos(usrdep))

    return render_template('documento.html', error=error, d=d, load_d=False, titulo='Registro de Documentos', tdocumentos=tdocu.get_tipo_documentos(usrdep))

@app.route('/documento_pdf/<doc_id>/<string:tipo>', methods=['GET', 'POST'])
@login_required
def documento_pdf(doc_id, tipo):
    dp = dpdf.Documentos_pdf(cxms)
    error = None  

    if request.method == 'POST':            
        f = request.files['archivo']
        filename = secure_filename(f.filename)
        filename = doc_id + '_' + filename        
        f.save(os.path.join('.' + app.config['SUBIR_PDF'], filename))
        fpath = os.path.join(app.config['SUBIR_PDF'], filename)
        fpath1 = os.path.join('.' + app.config['SUBIR_PDF'] + '/')
        arch, ext = os.path.splitext(fpath)
        name_to_save = str(doc_id) + "_" + str(tipo) + ext
        ruta = app.config['SUBIR_PDF'] + '/' + name_to_save
        os.rename(fpath1 + filename, fpath1 + name_to_save)        

        if dp.upd_documentopdf_id(doc_id, ruta) == True: 
                return render_template('documentos_list.html', documentos=dp.get_documentospdf_all(usrdep), puede_adicionar='Documentos - Adición' in permisos_usr)

    return render_template('documento_pdf.html', error=error, dp=dp, load_dp=False, puede_editar='Documentos - Edición' in permisos_usr)


@app.route('/documento_del/<doc_id>/<tipo_d>', methods=['GET', 'POST'])
@login_required
def documento_del(doc_id, tipo_d):
    d = docu.Documentos(cxms)
    d.del_documento(doc_id)      
    tipod = doc_id + "_" + tipo_d + '.pdf'
    tipod = tipod.lower()                    
    ejemplo_dir = os.path.join('.' + app.config['SUBIR_PDF'] + '/')
    directorio = pathlib.Path(ejemplo_dir)
    for fichero in directorio.iterdir():
        if fichero.name == tipod:
                os.remove(ejemplo_dir + fichero.name)
    rows = d.get_documentos_all(usrdep)
    if rows:
        return render_template('documentos_list.html', documentos=rows, puede_adicionar='Documentos - Adición' in permisos_usr)
    else:
        print ('Sin documentos...')    
#Codigo Grover-Final

@app.route('/asientos_list', methods=['GET', 'POST'])
@login_required
def asientos_list():
    a = asi.Asientos(cxms)
    rows = a.get_asientos_all(usrdep)
    if rows:
        if permisos_usr:    # tiene pemisos asignados
            return render_template('asientos_list.html', asientos=rows, puede_adicionar='Asientos - Adición' in permisos_usr)  # render a template
        else:
            return render_template('msg.html', l1='Sin permisos asignados !!')
    else:
        print ('Sin asientos...')


@app.route('/asiento/<idloc>', methods=['GET', 'POST'])
@login_required
def asiento(idloc):
    a = asi.Asientos(cxms)
    d = docu.Documentos(cxms)

    error = None
    p = ('Asientos - Edición' in permisos_usr)  # t/f

    '''
    # historico
    IP_remoto = request.remote_addr
    hloc_loc2 = historicolocloc2.Historicolocloc2() # -> bdge
    #
'''
    if request.method == 'POST':
        fa = request.form['fechaAct'][:-7]
        if idloc == '0':  # es NEW
            if False:   # valida si neces POST
                #error = "El usuario: " + request.form['uname']  + " ya existe...!"
                #return render_template('asiento.html', error=error, u=u, load_u=True)
                print('msg-err')
            else:
                nextid = a.get_next_idloc()
                a.add_asiento(nextid, request.form['deploc'], request.form['provloc'], \
                              request.form['secloc'], request.form['nomloc'], request.form['poblacionloc'], \
                              request.form['poblacionelecloc'], request.form['fechacensoloc'], request.form['tipolocloc'], \
                              request.form['marcaloc'], request.form['latitud'], request.form['longitud'], \
                              request.form['estado'], '')

                a.add_asiento2(nextid, request.form['etapa'], \
                              request.form['obsUbicacion'], request.form['obs'], request.form['fechaIngreso'][:-7], \
                              fa, request.form['usuario'], request.form['docAct'], request.form['docRspNal'])

                d.upd_doc(request.form['docAct'], request.form['docRspNal'], request.form['doc_idAct'], request.form['doc_idRspNal'])

                rows = a.get_asientos_all(usrdep)
                return render_template('asientos_list.html', asientos=rows)  # render a template
        else: # Es Edit
            a.upd_asiento(idloc, request.form['nomloc'], request.form['poblacionloc'], \
                          request.form['poblacionelecloc'], request.form['fechacensoloc'], request.form['tipolocloc'], \
                          request.form['marcaloc'], request.form['latitud'], request.form['longitud'], \
                          request.form['estado'], '')

            fa = str(datetime.datetime.now())[:-7]     # fechaAct
            if a.existe_en_loc2(idloc):
                # Debe actualizar fechaAct y usuario

                a.upd_asiento2(idloc, request.form['etapa'], \
                              request.form['obsUbicacion'], request.form['obs'], \
                              str(request.form['fechaIngreso']), fa, usr, request.form['docAct'], request.form['docRspNal'])

                d.upd_doc(request.form['docAct'], 0, request.form['doc_idAct'], request.form['doc_idRspNal'])

            else:
                a.add_asiento2(idloc, request.form['etapa'], request.form['obsUbicacion'], \
                               request.form['obs'], request.form['fechaIngreso'], fa, request.form['usuario'], \
                               request.form['docAct'], request.form['docRspNal'])

                d.upd_doc(request.form['docAct'], 0, request.form['doc_idAct'], request.form['doc_idRspNal'])

            rows = a.get_asientos_all(usrdep)
            return render_template('asientos_list.html', asientos=rows, puede_adicionar='Asientos - Adición' in permisos_usr)  # render a template
    else: # Viene de <asientos_list>
        if idloc != '0':  # EDIT
            if a.get_asiento_idloc(idloc) == True:
                """if a.docAct == None:
                    a.docAct = """
                if a.fechaIngreso == None:
                    a.fechaIngreso = str(datetime.datetime.now())[:-7]
                if a.fechaAct == None:
                    a.fechaAct = str(datetime.datetime.now())[:-7]
                if a.usuario == None:
                    a.usuario = usr

                return render_template('asiento.html', error=error, a=a, load=True, puede_editar=p, tpdfsA=d.get_tipo_documentos_pdfA(usrdep), tpdfsRN=d.get_tipo_documentos_pdfRN(usrdep))

    # New
    return render_template('asiento.html', error=error, a=a, load=False, puede_editar=p, tpdfsA=d.get_tipo_documentos_pdfA(usrdep), tpdfsRN=d.get_tipo_documentos_pdfRN(usrdep))


@app.route('/reportes', methods=['GET', 'POST'])
@login_required

def reportes():
    a = asi.Asientos(cxms)
    return render_template('reportes.html', load=True, resultados=a.get_geo_all(usrdep))
    

@app.route('/about')
def about():
    return render_template('public/about.html')


@app.route('/welcome')
def welcome():
    return render_template('welcome.html')

@app.route('/habilitado')
@login_required
def habilitado():
    return render_template('habilitado.html')

@app.route('/secret')
@login_required
def secret():
    return ('DEBERIA check only auths..')


@app.route('/logout', methods=['GET', 'POST'])
@login_required
def logout():

    #user = current_user
    #user.authenticated = False
    #db.session.add(user)
    #db.session.commit()
    logout_user()
    return render_template('/logout.html')  # render a template

# Código adicionado david

# ****** CREA LINK SCRIPT MUNDO
@app.route('/mundo', methods=['GET', 'POST'])
@login_required
def mundo():
    print(('mundoooooooooooo'))

    m = mun.Paisgral()
    rows = m.get_paisgeneral()

    if rows == False:
        print ('Sin datos del mundo...')
    else:
        print(rows[0])
        print('----------------------------')
        print(rows[1])

        #return('add..tabla para mundo')
        return render_template('mundo.html', datos_mundo=rows)  # render a template


# ****** CREA LINK PARA PAISES
@app.route('/pais', methods=['GET', 'POST'])
@login_required
def pais():
    p = paises.Pais()
    rows = p.get_paises()

    if rows == False:
        print ('Sin datos departamentos...')
    else:
        print(rows[0])
        #return 'mostrar tabla con paises'
        return render_template('paises.html', datos_paises=rows)  # render a template

# ****** FORM PARA PAIS
@app.route('/pais_add/<pais_id>', methods=['GET', 'POST'])
@login_required
def pais_add(pais_id=None):
    print(pais_id)
    p = paises.Pais()
    rows = p.get_paises()

    error = None

    if request.method == 'POST':
        print('===RESULTADO DEL BOTON FORM en POST Aceptar')
        print(pais_id)

        if pais_id == '0':  # es NEW PAIS
            print('++++++++ LISTOS A guardar en bd dbo.PAIS +++++++')
            print(request.form['fIdPais'])
            print(request.form['fPais'])
            print(request.form['fNomPais'])

            p.add_pais(request.form['fIdPais'], \
                        request.form['fPais'], \
                        request.form['fNomPais'], \
                        request.form['fNacionalidad'], \
                        request.form['fEstado'], \
                        request.form['fCodigoInternacional'], \
                     #    request.form['fIdPais'])
                        request.form['fCodigoInternacionalISO3166'])

            return render_template('welcome.html')

        else:   # UPDATE distinto a 0
            #return 'luego de editar el FORM PARA ACTUALIZAR la BD'
            p.upd_pais(pais_id, \
                            request.form['fPais'], \
                            request.form['fNomPais'], \
                            request.form['fNacionalidad'], \
                            request.form['fEstado'], \
                            request.form['fCodigoInternacional'], \
                            request.form['fCodigoInternacionalISO3166'])

            return render_template('welcome.html')

    else: # VIENE DEL LISTADO TABLA PAISES BOTON EDITAR
        if pais_id != 0:  # EDIT
            print('viene del Form Paises para editar')
            if p.get_pais_id(pais_id) == True:
                print('<<<<<<< RETORNO DE CONSULTA>>>>>>>>>')
                print(p.Pais)
                print(p.NomPais)
                print(p.Nacionalidad)
                print(p.Estado)
                #return render_template('departamento_add.html', error=error, u=u, load_u=True)
                return render_template('pais_add.html',p=p,Pais_id=pais_id,load_u=True)

    #return 'para add paises'
    return render_template('pais_add.html',datos_paises=rows)


# ****** CREA LINK PARA DEPTOS
@app.route('/deptos', methods=['GET', 'POST'])
@login_required
def deptos():
    d = departamentos.Departamentos()
    rows = d.get_departamentos()

    print('---> entra tabla Departamennto')

    # GRABANDO TABLA  prueba-1
    #print('USUARIO---> '+us_ingresa_sesion)
    #IP_remoto = request.remote_addr
    #print('ip remoto -> '+IP_remoto)
    print('---> DAto insertado a prueba-1')

    if rows == False:
        print ('Sin datos departamentos...')
    else:
        print(rows[0])
        #return 'mostrar tabla con departamentos'
        return render_template('departamentos.html', datos_deptos=rows)  # render a template


# ****** FORM PARA DEPTOS
@app.route('/departamento_add/<depto_id>', methods=['GET', 'POST'])
@login_required
def departamento_add(depto_id=None):

    print('addddddddddddddddddddddddddddddddddd')
    print(depto_id)

    p = paises.Pais()
    rows = p.get_paises()

    d = departamentos.Departamentos()
    rows_d =  d.get_departamentos()

    # HISTORICO
    IP_remoto = request.remote_addr

    hdep = historicodep.Historicodep()   # -> bdge
    d2 = departamento2.Departamento2()   # -> bdge


    error = None

    if request.method == 'POST':
        print('===RESULTADO DEL BOTON FORM en POST por new & sendbutton')
        print(depto_id)

        if depto_id == '0':  # es NEW
            print('++++++++ LISTOS A guardar en bbdd ++++++++++++++++')
            print(request.form['fDep'])
            print(request.form['fNomDep'])
            print('***  select  ***')
            print(request.form['fNomPais'])

            d.add_departamento(request.form['fDep'], \
                    #            request.form['fDep'], \
                        request.form['fNomDep'], \
                        request.form['fDiputados'], \
                        request.form['fDiputadosUninominales'], \
                    #    request.form['fIdPais'])
                        request.form['fNomPais'])

            d2.add_dep2(request.form['fDep'], \
                request.form['fNomDep'], \
                request.form['fNomPais'], \
                request.form['fNomPais'], \
                request.form['fNomPais'], \
                request.form['fNomPais'])

            # REGISTRA EN HISTORICO-DEP   NUEVO-REG
            hdep.add_historicodep(us_ingresa_sesion, IP_remoto, 'Add registro',0,'nn',0,0,0, \
                request.form['fDep'], \
                request.form['fNomDep'], \
                request.form['fDiputados'], \
                request.form['fDiputadosUninominales'], \
                request.form['fNomPais'], \
                'Nuevo registro'
                )

            return render_template('welcome.html')

        else:   # UPDATE
            #return 'luego de editar el FORM PARA ACTUALIZAR la BD'
            if d.get_departamento_id(depto_id) == True:
                print('<<<<<<< RETORNO DATOS ACTUALES ANTES D UPDATE>>>>>>>>>')
                print(d.NomDep)
                print(d.Diputados)
                print(d.DiputadosUninominales)
                print(d.IdPais)

            d.upd_departamento(depto_id, \
                            request.form['fNomDep'], \
                            request.form['fDiputados'], \
                            request.form['fDiputadosUninominales'], \
                            request.form['fNomPais'])


            # COMPARA CAMPOS ANTERIOR VS ACTUAL
            cambioReg = ' '
            if str(d.NomDep).strip() != str(request.form['fNomDep']).strip():
                cambioReg = cambioReg + 'NombDep, '
            if d.Diputados != int(request.form['fDiputados']):
                cambioReg = cambioReg + 'Diputados, '
            if d.DiputadosUninominales != int(request.form['fDiputadosUninominales']):
                cambioReg = cambioReg + 'DiputadosUninominales, '
            if d.IdPais != int(request.form['fNomPais']):
                cambioReg = cambioReg + 'IdPais '
            print(cambioReg)

            # REGISTRA EN HISTORICO-DEP  ACTUALIZACION
            hdep.add_historicodep(us_ingresa_sesion, IP_remoto, 'Actualiza Registro', \
                depto_id, d.NomDep, d.Diputados, d.DiputadosUninominales, d.IdPais, \
                depto_id, \
                request.form['fNomDep'], \
                request.form['fDiputados'], \
                request.form['fDiputadosUninominales'], \
                request.form['fNomPais'], \
                cambioReg
                )

            return render_template('welcome.html')

    else: # VIENE DEL LISTADO DEPARTAMENTOS BOTON EDITAR
        if depto_id != 0:  # EDIT
            print('viene del Form para editar')
            if d.get_departamento_id(depto_id) == True:
                print('<<<<<<< RETORNO DE CONSULTA>>>>>>>>>')
                print(d.NomDep)
                print(d.Diputados)
                print(d.DiputadosUninominales)
                print(d.IdPais)

                # OBTIENE PAIS EN BASE A depto_id
                #print(rows[int(d.IdPais)-1])
                for c in rows:
                    if c[0] == int(d.IdPais):
                        print(c)
                        nomPais = c[2]
                        indPAis = c[0]
                #return 'viene del Form para editar'
                #return render_template('departamento_add.html', error=error, u=u, load_u=True)
                return render_template('departamento_add.html', d=d,Dep_id=depto_id, datos_paises=rows, nomPais=nomPais, indPAis=indPAis,load_u=True)

    print('<<<<<<  para obt ultimo indice >>> Dep')
    for ind in rows_d:
        indice = ind[0]

    ind_depto = int(indice)+1
    #print(ind_depto)

    return render_template('departamento_add.html', datos_paises=rows, ind_depto=ind_depto)

# ****** PARA ELIMINAR DEPARTAMENTO
@app.route('/departamento_del/<depto_id>', methods=['GET', 'POST'])
def departamento_del(depto_id):

    d = departamentos.Departamentos()
    # HISTORICO
    IP_remoto = request.remote_addr
    hdep = historicodep.Historicodep()   # -> bdge

    print('-------------------')
    print('EN func. para eliminar --->'+ depto_id)
    print('-------------------')
    if d.get_departamento_id(depto_id) == True:
        print('<<<<<<< Obtiene datos a ELIMINAR >>>>>>>>>')
        print(d.NomDep)
        print(d.Diputados)
        print(d.DiputadosUninominales)
        print(d.IdPais)

    d.del_departamento(depto_id)

    # REGISTRA EN HISTORICO-DEP ELIMINACION
    hdep.add_historicodep(us_ingresa_sesion, IP_remoto, 'Elimino Registro', \
        depto_id, d.NomDep, d.Diputados, d.DiputadosUninominales, d.IdPais, \
        0, 'nn', 0, 0, 0, \
        'Registro Eliminado'
    )


    rows = d.get_departamentos()
    if rows == False:
        print ('Sin departamentos...')
    else:
        return render_template('departamentos.html', datos_deptos=rows)  # render a template
    # return 'Para eliminar un departamento'


# ****** CREA LINK PARA PROVINCIAS
@app.route('/provincias', methods=['GET', 'POST'])
@login_required
def provincias():
    pv = prov.Provincia()
    rows = pv.get_provincias()

    print('prov normal...')

    if rows == False:
        print ('Sin datos departamentos...')
    else:
        print(rows[0])
        #return 'mostrar tabla con provincias'
        return render_template('provincias.html', datos_prov=rows)  # render a template

# ****** FORM PARA PROVINCIAS
@app.route('/provincia_add/<prov_id>', methods=['GET', 'POST'])
@login_required
def provincia_add(prov_id=None):
    print(prov_id)

    d = departamentos.Departamentos()
    rows = d.get_departamentos()
    print(rows[0])

    pv =  prov.Provincia()
    error = None

    if request.method == 'POST':
        print('===RESULTADO DEL BOTON FORM en POST Aceptar')
        print(prov_id)

        if prov_id == '0':  # es NEW PAIS
            print('++++++++ LISTOS A guardar en bd dbo.PROV +++++++')
            print(request.form['fDepProv'])
            print(request.form['fProv'])
            print(request.form['fNomProv'])

            pv.add_provincia(request.form['fDepProv'], \
                        request.form['fProv'], \
                        request.form['fNomProv'], \
            #            request.form['fNacionalidad'], \
            #            request.form['fEstado'], \
            #            request.form['fCodigoInternacional'], \
                     #    request.form['fIdPais'])
                        request.form['fcodprov'])

            return render_template('welcome.html')


    #return 'Form para provincias'
    return render_template('provincia_add.html', datos_depto=rows)


@app.route('/provincia_add2/<prov_id>/<prov_id2>', methods=['GET', 'POST'])
@login_required
def provincia_add2(prov_id=None, prov_id2=None):
    print(prov_id)
    d = departamentos.Departamentos()
    rows = d.get_departamentos()

    pv =  prov.Provincia()

    if request.method == 'POST':
        print('<<< para actualizar form provincia en bbdd >>>')
            #return 'luego de editar el FORM PARA ACTUALIZAR la BD'
        pv.upd_provincia(prov_id, prov_id2, \
                        request.form['fNomProv'], \
                        request.form['fcodprov'])

        return render_template('welcome.html')



    else: # VIENE DEL LISTADO DEPARTAMENTOS BOTON EDITAR
        print('viene del Form para editar')

        if pv.get_provincia_id(prov_id, prov_id2) == True:
            print('<<<<<<< RETORNO DE CONSULTA>>>>>>>>>')
            print(pv.NomProv)
            print(pv.codprov)

            # OBTIENE DEPTO EN BASE A prov_id
            #print(rows[int(d.IdPais)-1])
            for c in rows:
                if c[0] == int(prov_id):
                    print(c)
                    nomDepto = c[1]

            #return render_template('departamento_add.html', error=error, u=u, load_u=True)
            #return render_template('provincia_add.html', d=d,Dep_id=depto_id, datos_paises=rows, nomPais=nomPais,load_u=True)
            return render_template('provincia_add.html', pv=pv, DepProv1=prov_id, nomDepto=nomDepto, Prov2=prov_id2, load_u=True)

    return 'prov 2 parametros'
    #return render_template('provincia_add.html')

# ****** PARA ELIMINAR PROVINCIA
@app.route('/provincia_del/<prov_id1>/<prov_id2>', methods=['GET', 'POST'])
def provincia_del(prov_id1,prov_id2):
    print(prov_id1)
    print(prov_id2)
    #return 'Para eliminar una provincia'

    #d = departamentos.Departamentos()
    #sc = secc.Seccion()
    pv =  prov.Provincia()

    print('-------------------')
    print('EN func. para eliminar prov --->'+ prov_id2)
    print('-------------------')

    #d.del_departamento(depto_id)
    #sc.del_seccion(secc_id1,secc_id2,secc_id3)
    pv.del_provincia(prov_id1, prov_id2)

    #rows = d.get_departamentos()
    #rows = sc.get_seccion()
    rows = pv.get_provincias()
    if rows == False:
        print ('Sin provincias...')
    else:
        return render_template('provincias.html', datos_prov=rows)  # render a template
    # return 'Para eliminar una provincia'



#**************   TRES  PARAMETROS
# ****** CREA LINK PARA SECCION
@app.route('/seccion', methods=['GET', 'POST'])
@login_required
def seccion():
    sc = secc.Seccion()
    rows = sc.get_seccion()

    print('SECC normal...')

    if rows == False:
        print ('Sin datos provincias...')
    else:
        print(rows[0])
        #return 'mostrar tabla con secciones'
        return render_template('seccion.html', datos_secc=rows)  # render a template


# ****** FORM PARA SECCIONES
@app.route('/seccion_add/<secc_id>', methods=['GET', 'POST'])
@login_required
def seccion_add(secc_id=None):
    print(secc_id)

    d = departamentos.Departamentos()
    rows_d = d.get_departamentos()
    print(rows_d[0])

    pv =  prov.Provincia()
    rows_p = pv.get_provincias()
    print(rows_p[0])

    sc = secc.Seccion()
    error = None

    #return 'Form para SECCIONES' #0ro INICIO

    if request.method == 'POST':  #2do
        print('===RESULTADO DEL BOTON FORM en POST Aceptar')
        print(secc_id)

        if secc_id == '0':  # es NEW seccion
            print('++++++++ LISTOS A guardar en bd dbo.PROV +++++++')
            #return '+++++++++LISTOS PARA GUARDAR A BBDD'
            print(request.form['fDepSec'])
            print(request.form['fProvSec'])
            print(request.form['fSec'])
            print(request.form['fNomSec'])
            #return '+++++++++LISTOS PARA GUARDAR A BBDD'
            sc.add_seccion(request.form['fDepSec'], \
                        request.form['fProvSec'], \
                        request.form['fSec'], \
                        request.form['fNumConceSec'], \
                        request.form['fNomSec'], \
                        request.form['fCircunSec'], \
                        request.form['fCodProv'], \
            #            request.form['fEstado'], \
                        request.form['fCodSecc'])

            return render_template('welcome.html')


    #return 'Form para OJO secciones'  # 1ra vez entra form (vacio)
    return render_template('seccion_add.html', datos_depto=rows_d, datos_prov=rows_p)


@app.route('/seccion_add2/<secc_id1>/<secc_id2>/<secc_id3>', methods=['GET', 'POST'])
@login_required
def seccion_add2(secc_id1=None, secc_id2=None, secc_id3=None):
    print(secc_id1)
    print(secc_id2)
    print(secc_id3)


    d = departamentos.Departamentos()
    rows_d = d.get_departamentos()

    pv =  prov.Provincia()
    rows_p = pv.get_provincias()

    #pv =  prov.Provincia()
    sc = secc.Seccion()
    #return 'SECCION 3 parametros'

    if request.method == 'POST':   # 2DO VIENE DEL FORM
        print('<<< para actualizar form SECCION en bbdd >>>')
            #return 'luego de editar el FORM PARA ACTUALIZAR la BD'
        sc.upd_seccion(secc_id1, secc_id2, secc_id3, \
                        request.form['fNumConceSec'], \
                        request.form['fNomSec'], \
                        request.form['fCircunSec'], \
                        request.form['fCodProv'], \
                        request.form['fCodSecc'])

        return render_template('welcome.html')

    else: # 1RO VIENE DEL LISTADO SECCIONES BOTON EDITAR
        print('viene del Form para editar')

        if sc.get_seccion_id(secc_id1, secc_id2, secc_id3) == True:
            print('<<<<<<< RET CONSULTA 3 param a SECCION >>>>>>>>>')
            print(sc.NomSec)
            print(sc.CodSecc)

            # OBTIENE DEPTO EN BASE A secc_id1
            for c in rows_d:
                if c[0] == int(secc_id1):
                    print(c[1])
                    nomDepto = c[1]

            # OBTIENE PROV EN BASE A secc_id1 secc_id2
            for c in rows_p:
                if c[0] == int(secc_id1) and c[1] == int(secc_id2):
                    print(c[2])
                    nomProv = c[2]

            #return render_template('provincia_add.html', d=d,Dep_id=depto_id, datos_paises=rows, nomPais=nomPais,load_u=True)
            return render_template('seccion_add.html', sc=sc, nomDepto=nomDepto, nomProv=nomProv, codSec=secc_id3, load_u=True)

    return 'seccion 3 parametros'
    #return render_template('provincia_add.html')

# ****** PARA ELIMINAR SECCION
@app.route('/seccion_del/<secc_id1>/<secc_id2>/<secc_id3>', methods=['GET', 'POST'])
def seccion_del(secc_id1,secc_id2,secc_id3):
    print(secc_id1)
    print(secc_id2)
    print(secc_id3)
    #return 'Para eliminar una SECCION'

    #d = departamentos.Departamentos()
    sc = secc.Seccion()

    print('-------------------')
    print('EN func. para eliminar seccion --->'+ secc_id3)
    print('-------------------')

    #d.del_departamento(depto_id)
    sc.del_seccion(secc_id1,secc_id2,secc_id3)

    #rows = d.get_departamentos()
    rows = sc.get_seccion()
    if rows == False:
        print ('Sin secciones...')
    else:
        return render_template('seccion.html', datos_secc=rows)  # render a template
    # return 'Para eliminar un departamento'


# ****** CREA LINK PARA LOCALIDADDES
@app.route('/localidad', methods=['GET', 'POST'])
@login_required
def localidad():
    loc = locc.Localidad()
    rows = loc.get_localidad()
    print('localidades normal...')

    if rows == False:
        print ('Sin datos localidad...')
    else:
        print(rows[0])
        #return 'mostrar tabla con localidad'
        return render_template('localidad.html', datos_loc=rows)  # render a template



# ****** CREA LINK PARA CIRCUNSCRIPCION
@app.route('/circun', methods=['GET', 'POST'])
@login_required
def circun():

    cir = ccircun.Circunscripcion()
    rows = cir.get_circunscripcion()
    print('circunscripcion normal...')

    if rows == False:
        print ('Sin datos localidad...')
    else:
        print(rows[0])
        #return 'mostrar tabla con circun'
        return render_template('circunscripcion.html', datos_circun=rows)  # render a template

# ****** FORM PARA CIRCUNSCRIPCION
@app.route('/circunscripcion_add/<circun_id>', methods=['GET', 'POST'])
@login_required
def circunscripcion_add(circun_id=None):
    print(circun_id)

    d = departamentos.Departamentos()
    rows_d = d.get_departamentos_bol()

    c = locloc.Tipocircunscripcion()
    rows_c = c.get_tipocircunscripcion()

    print(rows_d[0])
    print(rows_c[0])

    cir = ccircun.Circunscripcion()
    error = None

    if request.method == 'POST':
        print('===RESULTADO DEL BOTON FORM en POST Aceptar')
        print(circun_id)


        if circun_id == '0':  # es NEW PAIS
            print('++++++++ LISTOS A guardar en bd dbo.PROV +++++++')
            print(request.form['fDepCircun'])
            print(request.form['fCircun'])
            print(request.form['fNomCircun'])
            print(request.form['fTipoCircun'])
            #return '+++++Form para circunscripcion'

            cir.add_circunscripcion(request.form['fDepCircun'], \
                        request.form['fCircun'], \
                        request.form['fNomCircun'], \
                        request.form['fTipoCircun'])

            return render_template('welcome.html')


    #return 'Form para circunscripcion 1ra vez'
    return render_template('circunscripcion_add.html', datos_depto=rows_d, datos_tipocircun=rows_c)


@app.route('/circunscripcion_add2/<circun_id1>/<circun_id2>', methods=['GET', 'POST'])
@login_required
def circunscripcion_add2(circun_id1=None, circun_id2=None):
    print(circun_id1)
    d = departamentos.Departamentos()
    rows_d = d.get_departamentos_bol()

    c = locloc.Tipocircunscripcion()
    rows_c = c.get_tipocircunscripcion()

    cir = ccircun.Circunscripcion()
    #return 'luego de editar el FORM PARA ACTUALIZAR la BD'

    if request.method == 'POST':
        print('<<< para actualizar form circunscripcion en bbdd >>>')
          #return 'luego de editar el FORM PARA ACTUALIZAR la BD'
        cir.upd_circunscripcion(circun_id1, circun_id2, \
                        request.form['fNomCircun'], \
                        request.form['fTipoCircun'])

        return render_template('welcome.html')



    else: # VIENE DEL LISTADO DEPARTAMENTOS BOTON EDITAR
        print('viene del Form para editar')

        if cir.get_circunscripcion_id(circun_id1, circun_id2) == True:
            print('<<<<<<< RETORNO DE CONSULTA>>>>>>>>>')
            qnomCircun = cir.NomCircun
            print(qnomCircun)
            qtipoCircun = cir.TipoCircun
            print(qtipoCircun)


            # OBTIENE DEPTO EN BASE A circun_id1
            for c in rows_d:
                if c[0] == int(circun_id1):
                    #print(c)
                    nomDepto = c[1]
                    print(nomDepto)
            # OBTIENE TIPOCIRCUNSCRIP EN BASE A cir.TipoCircun
            for c in rows_c:
                if c[0] == int(qtipoCircun):
                    #print(c)
                    nomTipoCircun = c[1]
                    print(nomTipoCircun)

            #return 'circunscripcion todos parametros'

            #return render_template('departamento_add.html', error=error, u=u, load_u=True)
            #return render_template('provincia_add.html', d=d,Dep_id=depto_id, datos_paises=rows, nomPais=nomPais,load_u=True)
            return render_template('circunscripcion_add.html',datos_tipocircun=rows_c ,pv=cir, depCircun=circun_id1, nomDepto=nomDepto, circun=circun_id2, qtipoCircun=qtipoCircun,nomTipoCircun=nomTipoCircun, load_u=True)

    return 'circunscripcion 2 parametros'
    #return render_template('provincia_add.html')

# ****** PARA ELIMINAR CIRCUNSCRIPCION
@app.route('/circunscripcion_del/<circun_id1>/<circun_id2>', methods=['GET', 'POST'])
def circunscripcion_del(circun_id1, circun_id2):
    print(circun_id1)
    print(circun_id2)
    #return 'Para eliminar una ciecunscripcion'

    pv =  prov.Provincia()
    cir = ccircun.Circunscripcion()

    print('-------------------')
    print('EN func. para eliminar circun --->'+ circun_id1+','+circun_id2)
    print('-------------------')

    cir.del_circunscripcion(circun_id1, circun_id2)

    # para mostrar tabla actualizada
    rows = cir.get_circunscripcion()
    if rows == False:
        print ('Sin circunscripciones...')
    else:
        return render_template('circunscripcion.html', datos_circun=rows)  # render a template

    # return 'Para eliminar una provincia'



# start the server with the 'run()' method
if __name__ == '__main__':
    app.run(host='0.0.0.0', port='5000', debug=True)
