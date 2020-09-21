# Operaciones asientos

class Asientos:
    idloc = 0
    deploc = 0
    provloc = 0
    secloc = 0
    nomloc = ""
    poblacionloc = 0
    poblacionelecloc = 0
    fechacensoloc = ''
    tipolocloc = ''
    fechabaselegloc = ''
    marcaloc = ''
    latitud = 0
    longitud = 0
    estado = 0
    circunconsulado = ''

    etapa = 0
    obsUbicacion = ''
    obs = ''
    fechaIngreso = ''
    fechaAct = ''
    usuario = ''

    _departamento = ''
    _provincia = ''
    _municipio = ''
    _tipo_circun = ''

    def __init__(self, cx):
        self.cx = cx
        self.cur = cx.cursor()

    """def get_asientos(self, usrdep):
        s = "select IdLoc, NomDep as Departamento, NomProv as Provincia, NombreMunicipio as Municipio, "  + \
	    "AsientoElectoral as Asiento, NombreTipoLocLoc as Tipo_Circun, DEP, PROV, SEC " + \
	    " from [GeografiaElectoral_app].[dbo].[GeoAsientos_Nacional]"
        if usrdep != 0 :
            s = s + " where DEP = %d order by prov, sec"
            self.cur.execute(s, usrdep)
        else:
            s = s + " order by DEP, PROV, SEC"
            print(s)
            self.cur.execute(s)

        rows = self.cur.fetchall()
        if self.cur.rowcount == 0:
            return False
        else:
            return rows
    """
    def get_asientos_all(self, usrdep):        
        s = "select IdLoc, NomDep as Departamento, NomProv as Provincia, NombreMunicipio as Municipio, "  + \
	    "AsientoElectoral as Asiento, NombreTipoLocLoc as Tipo_Circun, DEP, PROV, SEC, " + \
            "CASE WHEN estado = 1 THEN 'Habilitado TED' " + \
                    "WHEN estado = 2 THEN 'Rehabilitado TED' " + \
                    "WHEN estado = 3 THEN 'Suspendido TED' " + \
                    "WHEN estado = 4 THEN 'Suprimido TED' " + \
                    "WHEN estado = 101 THEN 'Habilitado TSE' " + \
                    "WHEN estado = 102 THEN 'Rehabilitado TSE' " + \
                    "WHEN estado = 103 THEN 'Suspendido TSE' " + \
                    "WHEN estado = 104 THEN 'Suprimido TSE' " + \
            "ELSE 'oother'  END as Estado" + \
	    " from [bdge].[dbo].[GeoAsientos_Nacional_all]"
        if usrdep != 0 :
            s = s + " where DEP = %d order by prov, sec"
            self.cur.execute(s, usrdep)
        else:
            s = s + " order by DEP, PROV, SEC"
            print(s)
            self.cur.execute(s)

        rows = self.cur.fetchall()
        if self.cur.rowcount == 0:
            return False
        else:
            return rows

    def get_asiento_idloc(self, idloc):
        s = "select a.IdLoc, a.DepLoc, a.ProvLoc, a.SecLoc, a.NomLoc," + \
            "a.PoblacionLoc, a.PoblacionElecLoc, a.FechaCensoLoc, a.TipoLocLoc, a.fechaBaseLegLoc," + \
            "a.MarcaLoc, a.latitud, a.longitud, a.estado, a.circunConsulado," + \
            "b.NomDep as _departamento, c.NomProv as _provincia, d.NomSec as _municipio, e.NombreTipoLocLoc as _tipo_circun," + \
            "f.etapa, f.doc_idA, f.obsUbicacion, f.doc_idRN, f.obs, f.fechaIngreso, f.fechaAct, f.usuario " + \
            "from [GeografiaElectoral_app].[dbo].[LOC] a" + \
            "        left join [GeografiaElectoral_app].[dbo].[DEP] b on a.DepLoc= b.Dep " + \
            "        left join [GeografiaElectoral_app].[dbo].[PROV] c on a.DepLoc= c.DepProv and a.ProvLoc= c.Prov" + \
            "        left join [GeografiaElectoral_app].[dbo].[SEC] d on a.DepLoc= d.DepSec and a.ProvLoc= d.ProvSec and a.SecLoc= d.Sec" + \
            "        left join [GeografiaElectoral_app].[dbo].[clTipoLocLoc] e on a.TipoLocLoc = e.Tipolocloc" + \
            "        left join [bdge].[dbo].[loc2] f on a.idloc = f.idloc " + \
            "where a.idloc = %d "

        self.cur.execute(s, idloc)
        row = self.cur.fetchone()
        if  row == None:
            return False
        else:
            self.idloc = row[0]
            self.deploc = row[1]
            self.provloc = row[2]
            self.secloc = row[3]
            self.nomloc = row[4]

            self.poblacionloc = row[5]
            self.poblacionelecloc = row[6]
            self.fechacensoloc = row[7]
            self.tipolocloc = row[8]
            self.fechabaselegloc = row[9]

            self.marcaloc = row[10]
            self.latitud = row[11]
            self.longitud = row[12]
            self.estado = row[13]
            self.circunconsulado = row[14]
            self._departamento = row[15]
            self._provincia = row[16]
            self._municipio = row[17]
            self._tipo_circun = row[18]

            self.etapa = row[19]
            self.doc_idA = row[20]
            self.obsUbicacion = row[21]
            self.doc_idRN = row[22]
            self.obs = row[23]
            self.fechaIngreso = row[24]
            self.fechaAct = row[25]
            self.usuario = row[26]
            return True
    
    def add_asiento(self, idloc, deploc, provloc, \
                    secloc, nomloc, poblacionloc, \
                    poblacionelecloc, fechacensoloc, tipolocloc, \
                    marcaloc, latitud, longitud, \
                    estado, circunconsulado):

        new_asiento = idloc, deploc, provloc, secloc, 0, \
            0, nomloc, poblacionloc, poblacionelecloc, fechacensoloc, \
            tipolocloc, '2007-01-01', '', marcaloc, '', \
            '', 0, 0, 0, 0, \
            0, latitud, longitud, estado, circunconsulado

        s = "insert into GeografiaElectoral_app.dbo.loc (idloc, deploc, provloc, secloc, loc, " + \
            " idcanloc, nomloc, poblacionloc, poblacionelecloc, fechacensoloc, " + \
            " tipolocloc, fechabaselegloc, codbaselegloc, marcaloc, escabeceracanloc, " + \
            " escabecerasecloc, codprov, codsecc, tipocircun, circun, " + \
            " estadomapa, latitud, longitud, estado, circunconsulado) VALUES " + \
            " (%s, %s, %s, %s, %s,  %s, %s, %s, %s, %s,  %s, %s, %s, %s, %s,  %s, %s, %s, %s, %s,  %s, %s, %s, %s, %s )"
        try:
            self.cur.execute(s, new_asiento)
            self.cx.commit()
            print("asiento adicionado...")
        except:
            print("Error - actualización de asiento...")
    
    def add_asiento2(self, idloc, etapa, obsUbicacion, \
                    obs, fechaIngreso, fechaAct, usuario, docAct, docRspNal):

        new_asiento = idloc, etapa, obsUbicacion, \
                    obs, fechaIngreso, fechaAct, usuario, docAct, docRspNal

        s = "insert into  [bdge].[dbo].[loc2] (idloc, etapa, obsUbicacion, obs, fechaIngreso," + \
            " fechaAct, usuario, doc_idA, doc_idRN) VALUES " + \
            " (%s, %s, %s,  %s, %s, %s,  %s, %s, %s)"
        try:
            self.cur.execute(s, new_asiento)
            self.cx.commit()
            print("asiento -loc2- adicionado...")
        except Exception as e:
            print("Error - adición de asiento -loc2-...")
            print(e)
    
    def upd_asiento(self, idloc, nomloc, poblacionloc, \
                    poblacionelecloc, fechacensoloc, tipolocloc, \
                    marcaloc, latitud, longitud, \
                    estado, circunconsulado):
        '''
            NO actualiza datos de jurisdicción - dep, prov, sec
        '''

        asiento = nomloc, poblacionloc, \
                  poblacionelecloc, fechacensoloc, tipolocloc, \
                  marcaloc, latitud, longitud, \
                  estado, circunconsulado, idloc
        s = "update GeografiaElectoral_app.dbo.loc" + \
            " set nomloc= %s, poblacionloc= %d, " + \
            " poblacionelecloc= %s, fechacensoloc= %s, tipolocloc= %d, " + \
            " marcaloc= %d, latitud= %s, longitud= %d, " + \
            " estado= %d, circunconsulado= %s" + \
            " where idloc = %d"
        try:
            self.cur.execute(s, asiento)
            self.cx.commit()
            print('Asiento actualizado')
        except Exception as e:
            print("Error - actualización de Asiento...")
            print(e)
    
    def upd_asiento2(self, idloc, etapa, obsUbicacion, \
                    obs, fechaIngreso, fechaAct, usuario, docAct, docRspNal):

        asiento = etapa, obsUbicacion, \
                    obs, fechaIngreso, fechaAct, usuario, docAct, docRspNal, idloc

        s = "update bdge.dbo.loc2" + \
            " set etapa= %d, obsUbicacion= %s, obs= %s, fechaIngreso= %s," + \
            " fechaAct= %s, usuario= %s, doc_idA= %s, doc_idRN= %s" + \
            " where idloc = %d"
        try:
            self.cur.execute(s, asiento)
            self.cx.commit()
            print('Asiento actualizado -LOC2-')
        except Exception as e:
            print("Error - actualización de Asiento...-LOC2-")
            print(e)
    
    def get_next_idloc(self):
        self.cur.execute("select max(idloc) + 1 from GeografiaElectoral_app.dbo.loc")
        row = self.cur.fetchone()
        return row[0]
    
    def existe_en_loc2(self, idloc):
        s = "select idloc from bdge.dbo.loc2 where idloc= %d "
        self.cur.execute(s, idloc)
        row = self.cur.fetchone()
        if  row == None:
            return False
        else:
            return True

    def get_geo_all(self, usrdep):        
        s = "select IdLoc, Dep, NomDep, Prov, NomProv, Sec, NombreMunicipio, AsientoElectoral, latitud, longitud"  + \
        " from [bdge].[dbo].[GeoAsientos_Nacional_all]"
        if usrdep != 0 :
            s = s + " where DEP = %d order by prov, sec"
            self.cur.execute(s, usrdep)
        else:
            s = s + " order by DEP, PROV, SEC"
            print(s)
            self.cur.execute(s)

        rows = self.cur.fetchall()
        if self.cur.rowcount == 0:
            return False
        else:
            return rows
    
    def __str__(self):
        return str(self.idloc) + '--' + self.nomloc
    
