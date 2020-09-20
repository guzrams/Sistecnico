<?php
include ("Funciones.php"); 
include ("Seguridad.php"); 
include ("Coneccion.php");
$conexion = conectar();
$s_cod_usuario=$_SESSION['s_cod_usuario'];
$accion="Creacion de FormularioSL";
vitacora($s_cod_usuario,$conexion,$accion);
Print_HeaderSIMMA3($usuario, $Cod_Adm_Usuarios, $usPass);
$cod_municipio=$_REQUEST['lst_municipio'];
$lst_gestion=$_REQUEST['lst_gestion'];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>SIGRACC</title>
<style type="text/css">
<!--
.style4 {
	font-size: 14px;
    font-weight: bold;
    line-height: 10px;
	color: #FFF3C6;
}
.Estilo1 {color: #FF0000}
.Estilo24 {font-family: Verdana, Arial, Helvetica, sans-serif}
.Estilo27 {font-size: 10px}
.Estilo28 {font-size: 12px}
-->
.style3 {color: #FFFFFF; font-weight: bold; }
.Estilo15 {color: #FFFFFF; font-weight: bold; font-size: 9px; font-family: Verdana, Arial, Helvetica, sans-serif; }
.Estilo17 {color: #000000; }
.Estilo18 {font-size: 8px; color: #000000; font-family: Verdana, Arial, Helvetica, sans-serif; }
.Estilo30 {font-size: 8px; color: #000000; font-family: Verdana, Arial, Helvetica, sans-serif; font-weight: bold; }
</style>
<style>
#mapa{
      width: 100%;
	  height: 100%;
	  float: left;
  	 }
#infor{
	   width: 100%;
	   height: 500px;
	   float: left;
  	  }
</style>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"> 
<link rel="stylesheet" type="text/css" href="css/estilo.css"/> 
<script type="text/javascript" src="js/cambiarPestanna.js"></script>
<script type="text/javascript" src="js/jquery-1.7.2.min.js"></script> 
<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?sensor=false"></script>
<script type="text/javascript" src="http://code.jquery.com/jquery-1.10.2.min.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
<script>
		//Variables Generales
		//Array para almacenar nuevos marcadores
		var marcadores_nuevos = [];
		var marcadores_bd = [];
		var mapa = null;//variable general para el mapa
		//Funcion para quitar marcdores de mapa
		function quitar_marcadores(lista)
		{
		 //Recorrer el array de marcadores
		 for(i in lista)
		 	{
			 //Quitar marcadores del Mapa
			 lista[i].setMap(null);
			}
		}
        $(document).on("ready", function(){
		
		   var formulario = $("#form1");
		   
		   var bolivia = new google.maps.LatLng(-17.163622, -64.565926);
		   //variable para configuracion inicial
		   var config = {
		   		zoom:6,
				center:bolivia,
				mapTypeId: google.maps.MapTypeId.TERRAIN
		   };
		   //variable mapa
		   mapa = new google.maps.Map( $("#mapa")[0], config );		   
		   google.maps.event.addListener(mapa, "click", function(event){
		   //mostrar una alerta al hacer click en el mapa
		   //alert(event.latLng);
		   var coordenadas = event.latLng.toString();
		   //remover los parentesis
		   coordenadas = coordenadas.replace("(", "");
		   coordenadas = coordenadas.replace(")", "");
		   
		   var lista = coordenadas.split(",");
		   //mostrar las coordenadas por separado
		   //alert("La coordenadas X es: "+ lista[0]);
		   //alert("La coordenadas Y es: "+ lista[1]);
		   
		   //variable para direccion punto o coordenada
		   var direccion = new google.maps.LatLng(lista[0], lista[1]);
		   //variable para marcador
		   var marcador = new google.maps.Marker({
		   		position: direccion, //la posicion del nuevo marcador
				map: mapa, //en que mapa se ubicara el marcador
				animation: google.maps.Animation.DROP, //como aparecera el marcador
				draggable: false //no permitir el arrastre del marcador 
		   });
		   //Pasar las coordenadas al formulario
		   formulario.find("input[name='cx']").val(lista[0]);
		   formulario.find("input[name='cy']").val(lista[1]);
		   //Ubicar el foco en el campo Titulo
		   formulario.find("input[name='titulo']").focus();
		   //Dejar 1 solo marcador en el mapa
		   //Guardar el marcador en el array
		   marcadores_nuevos.push(marcador); 
		    	
		   //google.maps.event.addListener(marcador, "click", function(){
		   		//mostrar una alerta al hacer click
				//alert(marcador.titulo);
		   //});
		   //Antes de ubicar el marcador en el mapa quitar todos los demas
		   //y asi dejar 1 solo
		   quitar_marcadores(marcadores_nuevos);
		   //Ubicar el marcador en el mapa	
		   //marcador.setMap(mapa);
		   });
		   //cargar puntos al terminar de cargar la pagina
		   listar();//funciona, ahora a graficar los puntos en el mapa
		});
		//fuera de Ready de Jquery
		//function para recuperar puntos de la bd
		function listar()
		{
		 var f=$("#form1");
		 //antes de listar marcadores
 		 //se deben quitar los anteriores del mapa		 
		 quitar_marcadores(marcadores_bd);
		 $.ajax({
				 type:"POST",
				 url:"iajax.php",
				 dataType:"JSON",
				 data:f.serialize()+"&tipo=listar",
				 success:function(data){
			 	 	 if(data.estado=="Ok")
					 		{
							 //alert("Hay puntos en la BD");
							 $.each(data.mensaje, function(i, item){
							 	//obtener las coordenadas del punto
								var posi = new google.maps.LatLng(item.cx, item.cy);//bien
								//cargar las propiedades del marcador
								var marca = new google.maps.Marker({
									idMarcador:item.cod_municipio,
									position:posi,
									titulo:item.municipio,
									poblacion:item.poblacion,
									superficie:item.superficie,
									van:item.van,
									pobreza:item.pobreza,
									tractores:item.tractores,
									costo:item.costo,
									implementos:item.implementos
								});
								var contenido='<TABLE BORDER="5" BORDERCOLOR="#006666" CELLSPACING="0">'+
								'<TR>'+
									 '<td width="50%" height="50%" bgcolor="#CCCCCC">'+'<div align="center">'+'<span class="Estilo1">'+'MUNICIPIO DE '+marca.titulo+'</span>'+'<br>'+
									 'Poblacion.:'+marca.poblacion+'<br>'+'Superficie (KM2).:'+marca.superficie+'<br>'+'Indice de Vulnerabilidad (VAN).:'+marca.van+'<br>'+
									 'Indice de Pobreza (%).:'+marca.pobreza+'<br>'+'Tractores Donados.:'+marca.tractores+'<br>'+
									 'Implementos.:'+marca.implementos+'<br>'+'Costo ($us).:'+marca.costo+'<br>'+
									 /*'<input type="button" onclick=abrir("DatosMunicipal.php"); value="Mostrar Mapa">'+'</div>'+*/
								'</TR>'+
								'</TABLE>';
								/*var contenido='<table="" border="1">'+'<tr>'+
								'<td width="50%" height="50%" bgcolor="#0066CC">'+'<div align="center">'+'Municipio de '+marca.titulo+'</div>'+
								'<a href="DatosMunicipal.php", target="_blank">'+'<div align="center">Datos Adicionales</div>'+'</a>'+
								'</tr>'+'</table>';*/
								var infocontenido = new google.maps.InfoWindow({content: contenido});
								//agregar evento click al marcador
								google.maps.event.addListener(marca, "click", function(){
									var formulario = $("#form1");
									var x = marca.idMarcador; 									
									//alert("Hiciste click en "+x+" - "+marca.titulo);
									infocontenido.open(mapa, marca);
								});
								//agregar el marcador a la variable marcadores BD
								marcadores_bd.push(marca);
								//ubicar el marcador en el mapa
								marca.setMap(mapa);
								
							 });
							}
						else
							{
							 alert("No Hay puntos en la BD");
							}	
				 },
				 beforeSend:function(){
				 },
				 complete:function(){
				
				 }
		 });
		}
function ActivaBoton()
{
	i = document.form1.lst_municipio.selectedIndex;
	j = document.form1.lst_gestion.selectedIndex;
	if((document.form1.lst_municipio.options[i].text!='Sel.')&&(document.form1.lst_gestion.options[j].text!='Sel.'))
	    {document.form1.cmd_buscar.disabled=false;}
	else
		{document.form1.cmd_buscar.disabled=true;}
}
function abrir(direccion)
{ 
    //var direccion="DatosMunicipal.php";
	var pantallacompleta=0;
	var herramientas=0;
	var direcciones=0;
	var estado=0;
	var barramenu=0;
	var barrascroll=0;
	var cambiatamano=0;
	var ancho=400;
	var alto=500;
	var sustituir=1;
    var izquierda = (screen.availWidth - ancho) / 2; 
    var arriba = (screen.availHeight - alto) / 2; 
    var opciones = "fullscreen=" + pantallacompleta + 
                   ",toolbar=" + herramientas + 
                   ",location=" + direcciones + 
                   ",status=" + estado + 
                   ",menubar=" + barramenu + 
                   ",scrollbars=" + barrascroll + 
                   ",resizable=" + cambiatamano + 
                   ",width=" + ancho + 
                   ",height=" + alto + 
                   ",left=" + izquierda + 
                   ",top=" + arriba; 

    var ventana = window.open(direccion,"ventana",opciones,sustituir); 
}
function AbrirIrma(direccion)
{ 
    //var direccion="DatosMunicipal.php";
	var pantallacompleta=1;
	var herramientas=0;
	var direcciones=0;
	var estado=0;
	var barramenu=0;
	var barrascroll=1;
	var cambiatamano=0;
	var ancho=500;
	var alto=400;
	var sustituir=1;
    var izquierda = (screen.availWidth - ancho) / 2; 
    var arriba = (screen.availHeight - alto) / 2; 
    var opciones = "fullscreen=" + pantallacompleta + 
                   ",toolbar=" + herramientas + 
                   ",location=" + direcciones + 
                   ",status=" + estado + 
                   ",menubar=" + barramenu + 
                   ",scrollbars=" + barrascroll + 
                   ",resizable=" + cambiatamano + 
                   ",width=" + ancho + 
                   ",height=" + alto + 
                   ",left=" + izquierda + 
                   ",top=" + arriba; 

    var ventana = window.open(direccion,"ventana",opciones,sustituir); 
}                     
</script>
</head>
<body onload="javascript:cambiarPestanna(pestanas,pestana1);">
<table width="100%" border="1">
  <tr>
    <td width="45%" height="620" valign="top">
	<div id="mapa">
    </div>
	</td>
    <td width="55%" valign="top"> 
	<div id="infor"> 
    <form id="form1" name="form1" method="post" action="BuscaMunicipio.php" valign="top" onSubmit="return ValidaCampos(this)">
      <table width="100%" border="0" background="Iconos/Fondo_Azulado.jpg">
        <tr>
          <td valign="top"><table width="100%" border="0" background="Iconos/Fondo_Azulado.jpg">
              <tr>
                <td height="15" bgcolor="#00CCFF"><div align="right"><font color="#000000" size="2" face="Verdana, Arial, Helvetica, sans-serif">Gesti&oacute;n.</font>:</div></td>
                <td valign="top">
				<select name="lst_gestion" onchange="ActivaBoton()">
				<option>Sel.
				<?php $ano=date("Y")-5;
				$anof=date("Y")+1;
				while($ano<=$anof)
					   {?>
						<option><?php echo $ano;						
						$ano++;?>
			     <?php }?>
				</select>
				</td>
              </tr>
              <tr>
                <td width="20%" height="15" bgcolor="#00CCFF"><div align="right"><font color="#000000" size="2" face="Verdana, Arial, Helvetica, sans-serif">Municipio de.:</font></div></td>
                <td width="80%" valign="top"><?php 
					$SQL = "select departamento.departamento, municipio.cod_municipio, municipio.municipio from departamento, municipio where";
					$SQL .= " departamento.cod_departamento=municipio.cod_departamento order by departamento.cod_departamento, municipio.municipio";
					$resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());?>
                    <select name="lst_municipio" id="lst_municipio" style=" font-size:11px;font-family: Verdana, Arial, Helvetica, sans-serif" onchange="ActivaBoton()">
                    <option>Sel.
                    <?php 
			  		while($row = pg_fetch_array($resultado))
				   		 {
					      if($row["cod_municipio"]==$lst_municipio)
 					 	  	   {?>                      
                        		<option selected="selected"><?php echo $row["departamento"]." : ".$row["municipio"];
							   }
						   else
						       {?>
		                        <option><?php echo $row["departamento"]." : ".$row["municipio"];
							   }
				         }?>
                    </select>
                    <label>
					<input name="prueba" type="hidden" id="prueba" value="<?php echo $cod_municipio;?>"/>
                    <input name="cmd_buscar" type="submit" id="cmd_buscar" value="Buscar" disabled>
                    </label>
				</td>
              </tr>
          </table></td>
        </tr>
        <tr>
          <td valign="top"><div class="contenedor">
              <div id="pestanas">
                <ul class="Estilo27" id="lista">
                  <li id="pestana1"><a href='javascript:cambiarPestanna(pestanas,pestana1);' class="Estilo24">Datos Municipales</a></li>
                  <li id="pestana2"><span class="Estilo24"><a href='javascript:cambiarPestanna(pestanas,pestana2);'>Prod. Agricola</a></span></li>
                  <li class="Estilo24" id="pestana3"><a href='javascript:cambiarPestanna(pestanas,pestana3);'>Prod. Pecuaria</a></li>
                  <li class="Estilo24" id="pestana4"><a href='javascript:cambiarPestanna(pestanas,pestana4);'>Ejec. Presup.</a></li>
                  <li class="Estilo24" id="pestana5"><a href='javascript:cambiarPestanna(pestanas,pestana5);'>Gestion de RA</a></li>
                  <li class="Estilo24" id="pestana6"><a href='javascript:cambiarPestanna(pestanas,pestana6);'>Contacto Muncipales</a></li>
                  <li id="pestana7"><span class="Estilo24"><a href='javascript:cambiarPestanna(pestanas,pestana7);'>Presencia MDRyt</a></span></li>
				  <li id="pestana8"><span class="Estilo24"><a href='javascript:cambiarPestanna(pestanas,pestana8);'>El Tiempo de Hoy</a></span></li>
				  <li id="pestana9"><span class="Estilo24"><a href='javascript:cambiarPestanna(pestanas,pestana9);'>IRMA</a></span></li>
                </ul>
              </div>
            <div id="contenidopestanas">
              <div class="Estilo27 Estilo28" id="cpestana1">
			  <?php
			   echo "<center><a href='http://www.ine.gob.bo/indice/atlasmunicipal.aspx' target='_blank'>Datos Municipales</a></center>";
			   ?>
			  </div>
              <div class="Estilo27 Estilo28" id="cpestana2">
			  <?php
			    if(($lst_gestion!="")&&($cod_municipio!=""))
				{
			    $SQL = "select municipio from municipio where cod_municipio='$cod_municipio'";
				$resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
				$row = pg_fetch_array($resultado);
				$municipio=$row["municipio"];
				$SQL = "select * from fuente where cod_municipio='$cod_municipio' and gestion='$lst_gestion'";
				$resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
				$row = pg_fetch_array($resultado);
				$gestion=$row["gestion"];
				$fuente=$row["fuente"];
				$cod_fuente=$row["cod_fuente"];	
				$observaciones=$row["observaciones"];				
				function TotalSuperficie($tipo_producto, $cod_municipio, $lst_gestion)
				{
				  $conexion = conectar();  
				  $SQL = "select sum(pagricola.superficiecultivada) as superficie from pagricola, tipo_producto, producto where pagricola.cod_producto=producto.cod_producto";
				  $SQL .= " and producto.cod_tipo_producto=tipo_producto.cod_tipo_producto and tipo_producto.tipo_producto='$tipo_producto'"; 
				  $SQL .= " and pagricola.cod_municipio='$cod_municipio' and pagricola.gestion='$lst_gestion' group by tipo_producto.tipo_producto";
				  $SQL .= " order by tipo_producto.tipo_producto";
				  $resultado = pg_query($conexion, $SQL);
				  $row = pg_fetch_array($resultado);
				  $superficie=$row["superficie"];		 
				  return($superficie);		
				}
				function TotalRendimiento($tipo_producto, $cod_municipio, $lst_gestion)
				{
				  $conexion = conectar();  
				  $SQL = "select sum(pagricola.rendimiento) as rendimiento from pagricola, tipo_producto, producto where pagricola.cod_producto=producto.cod_producto";
				  $SQL .= " and producto.cod_tipo_producto=tipo_producto.cod_tipo_producto and tipo_producto.tipo_producto='$tipo_producto'"; 
				  $SQL .= " and pagricola.cod_municipio='$cod_municipio' and pagricola.gestion='$lst_gestion' group by tipo_producto.tipo_producto";
				  $SQL .= " order by tipo_producto.tipo_producto";
				  $resultado = pg_query($conexion, $SQL);
				  $row = pg_fetch_array($resultado);
				  $rendimiento=$row["rendimiento"];		 
				  return($rendimiento);
				}
				function TotalProduccion($tipo_producto, $cod_municipio, $lst_gestion)
				{
				  $conexion = conectar();  
				  $SQL = "select sum(pagricola.produccion) as produccion from pagricola, tipo_producto, producto where pagricola.cod_producto=producto.cod_producto";
				  $SQL .= " and producto.cod_tipo_producto=tipo_producto.cod_tipo_producto and tipo_producto.tipo_producto='$tipo_producto'"; 
				  $SQL .= " and pagricola.cod_municipio='$cod_municipio' and pagricola.gestion='$lst_gestion' group by tipo_producto.tipo_producto";
				  $SQL .= " order by tipo_producto.tipo_producto";
				  $resultado = pg_query($conexion, $SQL);
				  $row = pg_fetch_array($resultado);
				  $produccion=$row["produccion"];		 
				  return($produccion);
				}
				function TotalPrecioVenta($tipo_producto, $cod_municipio, $lst_gestion)
				{
				  $conexion = conectar();  
				  $SQL = "select sum(pagricola.precioventa) as precioventa from pagricola, tipo_producto, producto where pagricola.cod_producto=producto.cod_producto";
				  $SQL .= " and producto.cod_tipo_producto=tipo_producto.cod_tipo_producto and tipo_producto.tipo_producto='$tipo_producto'"; 
				  $SQL .= " and pagricola.cod_municipio='$cod_municipio' and pagricola.gestion='$lst_gestion' group by tipo_producto.tipo_producto";
				  $SQL .= " order by tipo_producto.tipo_producto";
				  $resultado = pg_query($conexion, $SQL);
				  $row = pg_fetch_array($resultado);
				  $precioventa=$row["precioventa"];		 
				  return($precioventa);
				}
				?>
			  <table width="100%" border="0" cellpadding="0" cellspacing="0" bordercolor="#E7E7E3"  bgcolor="#E7E7E3">
				<tr>
				  <td height="42"  bgcolor="#000066"><p align="center" class="style4">MUNICIPIO <?php echo $municipio.", "."GESTION ".$lst_gestion;?></p></td>
				</tr>
				<tr>
				  <td><table width="100%" border="0">
					<tr>
					  <td><table width="100%" border="0">
						<tr>
						  <td width="30%" height="22" bgcolor="#00CCFF"><span class="Estilo15 Estilo17">Cultivos</span></td>
						  <td width="17%" bgcolor="#00CCFF"><span class="Estilo15 Estilo17">Superf.(ha)</span></td>
						  <td width="17%" bgcolor="#00CCFF"><span class="Estilo15 Estilo17">Rendi.(t/ha) </span></td>
						  <td width="17%" bgcolor="#00CCFF"><span class="Estilo15 Estilo17">Produc.(t)</span></td>
						  <td width="19%" bgcolor="#00CCFF"><span class="Estilo15 Estilo17">Precio (USD/t) </span></td>
						</tr>
						<?php						
						$i=0;
						$SQL = "select * from pagricola, tipo_producto, producto where producto.cod_tipo_producto=tipo_producto.cod_tipo_producto";
						$SQL .= " and pagricola.cod_producto=producto.cod_producto and pagricola.cod_municipio='$cod_municipio' and";
						$SQL .= " pagricola.gestion='$lst_gestion' order by tipo_producto.tipo_producto, producto.producto";
						$resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
						while($row = pg_fetch_array($resultado))
							{
							 $Tipo_Producto[$i]=$row["tipo_producto"];
							 $i++;
							}
						$i=0;$j=0;$sw=0;$TS=0;$TR=0;$TP=0;$TV=0; 
						$SQL = "select * from pagricola, tipo_producto, producto where producto.cod_tipo_producto=tipo_producto.cod_tipo_producto";
						$SQL .= " and pagricola.cod_producto=producto.cod_producto and pagricola.cod_municipio='$cod_municipio' and";
						$SQL .= " pagricola.gestion='$lst_gestion' order by tipo_producto.tipo_producto, producto.producto";
						$resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
						while($row = pg_fetch_array($resultado))
							{
							 $cod_pagricola=$row["cod_pagricola"];
							 if($sw==0)
									  {$tipo_producto=$row["tipo_producto"];?>						  
									   <tr>
									   <td bgcolor="#999999"><span class="Estilo18"><?php echo $row["tipo_producto"];?></span></td>
									   <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalSuperficie($tipo_producto, $cod_municipio, $lst_gestion),2,",",".");?></span></td>
									   <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalRendimiento($tipo_producto, $cod_municipio, $lst_gestion),2,",",".");?></span></td>
									   <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalProduccion($tipo_producto, $cod_municipio, $lst_gestion),2,",",".");?></span></td>
									   <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalPrecioVenta($tipo_producto, $cod_municipio, $lst_gestion),2,",",".");?></span></td>
									   </tr>
								 <?php $sw=1;
									  }				
							 if($row["tipo_producto"]==$Tipo_Producto[$i])
												  {$b=1;}
											  else
												  {
												   if($row["tipo_producto"]==$Tipo_Producto[$i+1])
															 {$tipo_producto=$row["tipo_producto"];?>
															  <tr>
															  <td bgcolor="#999999"><span class="Estilo18"><?php echo $row["tipo_producto"];?></span></td>
															  <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalSuperficie($tipo_producto, $cod_municipio, $lst_gestion),2,",",".");?></span></td>
															  <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalRendimiento($tipo_producto, $cod_municipio, $lst_gestion),2,",",".");?></span></td>
															  <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalProduccion($tipo_producto, $cod_municipio, $lst_gestion),2,",",".");?></span></td>
															  <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalPrecioVenta($tipo_producto, $cod_municipio, $lst_gestion),2,",",".");?></span></td>
															  </tr>
													   <?php }
												 }
										if($b==1)
												 {?>
												  <tr>
												  <td><span class="Estilo18"><?php echo $row["producto"];?></span></td>
												  <?php $TS=$TS+$row["superficiecultivada"];?>
												  <td><span class="Estilo18"><?php echo number_format($row["superficiecultivada"],2,",",".");?></span></td>
												  <?php $TR=$TR+$row["rendimiento"];?>
												  <td><span class="Estilo18"><?php echo number_format($row["rendimiento"],2,",",".");?></span></td>
												  <?php $TP=$TP+$row["produccion"];?>
												  <td><span class="Estilo18"><?php echo number_format($row["produccion"],2,",",".");?></span></td>
												  <?php $TV=$TV+$row["precioventa"];?>
												  <td><span class="Estilo18"><?php echo number_format($row["precioventa"],2,",",".");?></span></td>
												  </tr>
											<?php $j++;$i=$j-1;
												 }
						}?>	
						  <tr>
						  <td bgcolor="#666666"><span class="Estilo30">TOTAL</span></td>
						  <td bgcolor="#666666"><span class="Estilo30"><?php echo number_format($TS,2,",",".");?></span></td>
						  <td bgcolor="#666666"><span class="Estilo30"><?php echo number_format($TR,2,",",".");?></span></td>
						  <td bgcolor="#666666"><span class="Estilo30"><?php echo number_format($TP,2,",",".");?></span></td>
						  <td bgcolor="#666666"><span class="Estilo30"><?php echo number_format($TV,2,",",".");?></span></td>
						  </tr>			 		  
					  </table></td>
					</tr>
				  </table></td>
				</tr>    
				<tr>
				  <td height="25" bgcolor="#33CCFF">&nbsp;</td>
				</tr>	
				<tr>
				  <td height="25" bgcolor="#999999"><span class="Estilo15 Estilo17">Fuente.: <?php echo $fuente." ,".$gestion."; Nota.:".$observaciones ; ?></span></td>
				</tr>
				<tr>
				  <td><div align="justify"><hr align="center"></div></td>
				</tr>	
			  </table>
			  <?php }?>
			  </div>
              <div id="cpestana3">
			  <?php
			  if(($lst_gestion!="")&&($cod_municipio!=""))
				{
			  $SQL = "select * from fuentep where cod_municipio='$cod_municipio'";
			  $resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
			  $row = pg_fetch_array($resultado);
			  $fecha=substr($row["fecha"],0,4);
			  $fuentep=$row["fuentep"];
			  $cod_fuentep=$row["cod_fuentep"];	
			  $observaciones=$row["observaciones"];
			  function TotalPoblacion1($tipo_especie, $cod_municipio, $cod_tipo_produccion, $lst_gestion)
				{
				  $conexion = conectar();  
				  $SQL = "select sum(ppecuaria.poblacion) as poblacion from ppecuaria, tipo_especie, especie where ppecuaria.cod_especie=especie.cod_especie";
				  $SQL .= " and especie.cod_tipo_especie=tipo_especie.cod_tipo_especie and tipo_especie.tipo_especie='$tipo_especie'"; 
				  $SQL .= " and ppecuaria.cod_municipio='$cod_municipio' and ppecuaria.cod_tipo_produccion='$cod_tipo_produccion'";
				  $SQL .= " and ppecuaria.gestion='$lst_gestion' group by tipo_especie.tipo_especie order by tipo_especie.tipo_especie";
				  $resultado = pg_query($conexion, $SQL);
				  $row = pg_fetch_array($resultado);
				  $poblacion=$row["poblacion"];		 
				  return($poblacion);
				}
				function TotalTasaExtraccion1($tipo_especie, $cod_municipio, $cod_tipo_produccion, $lst_gestion)
				{
				  $conexion = conectar();  
				  $SQL = "select sum(ppecuaria.tasaextraccion) as tasaextraccion from ppecuaria, tipo_especie, especie where ppecuaria.cod_especie=especie.cod_especie";
				  $SQL .= " and especie.cod_tipo_especie=tipo_especie.cod_tipo_especie and tipo_especie.tipo_especie='$tipo_especie'"; 
				  $SQL .= " and ppecuaria.cod_municipio='$cod_municipio' and ppecuaria.cod_tipo_produccion='$cod_tipo_produccion'";
				  $SQL .= " and ppecuaria.gestion='$lst_gestion' group by tipo_especie.tipo_especie order by tipo_especie.tipo_especie";
				  $resultado = pg_query($conexion, $SQL);
				  $row = pg_fetch_array($resultado);
				  $tasaextraccion=$row["tasaextraccion"];		 
				  return($tasaextraccion);
				}
				function TotalRendimiento1($tipo_especie, $cod_municipio, $cod_tipo_produccion, $lst_gestion)
				{
				  $conexion = conectar();  
				  $SQL = "select sum(ppecuaria.rendimiento) as rendimiento from ppecuaria, tipo_especie, especie where ppecuaria.cod_especie=especie.cod_especie";
				  $SQL .= " and especie.cod_tipo_especie=tipo_especie.cod_tipo_especie and tipo_especie.tipo_especie='$tipo_especie'"; 
				  $SQL .= " and ppecuaria.cod_municipio='$cod_municipio' and ppecuaria.cod_tipo_produccion='$cod_tipo_produccion'";
				  $SQL .= " and ppecuaria.gestion='$lst_gestion' group by tipo_especie.tipo_especie order by tipo_especie.tipo_especie";
				  $resultado = pg_query($conexion, $SQL);
				  $row = pg_fetch_array($resultado);
				  $rendimiento=$row["rendimiento"];		 
				  return($rendimiento);
				}
				function TotalProduccion1($tipo_especie, $cod_municipio, $cod_tipo_produccion, $lst_gestion)
				{
				  $conexion = conectar();  
				  $SQL = "select sum(ppecuaria.produccion) as produccion from ppecuaria, tipo_especie, especie where ppecuaria.cod_especie=especie.cod_especie";
				  $SQL .= " and especie.cod_tipo_especie=tipo_especie.cod_tipo_especie and tipo_especie.tipo_especie='$tipo_especie'"; 
				  $SQL .= " and ppecuaria.cod_municipio='$cod_municipio' and ppecuaria.cod_tipo_produccion='$cod_tipo_produccion'";
				  $SQL .= " and ppecuaria.gestion='$lst_gestion' group by tipo_especie.tipo_especie order by tipo_especie.tipo_especie";
				  $resultado = pg_query($conexion, $SQL);
				  $row = pg_fetch_array($resultado);
				  $produccion=$row["produccion"];		 
				  return($produccion);
				}
				function ActivaProduccion($cod_municipio, $cod_tipo_produccion, $lst_gestion)
				{
				 $conexion = conectar();  
				 $SQL = "select * from ppecuaria, tipo_especie, especie where especie.cod_tipo_especie=tipo_especie.cod_tipo_especie";
				 $SQL .= " and ppecuaria.cod_especie=especie.cod_especie and ppecuaria.cod_municipio='$cod_municipio' and";
				 $SQL .= " ppecuaria.cod_tipo_produccion='$cod_tipo_produccion' and ppecuaria.gestion='$lst_gestion'";
				 $SQL .= " order by tipo_especie.tipo_especie, especie.especie";
				 $resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
				 if($row = pg_fetch_array($resultado))
						{return(1);}
					else 
						{return(0);}
				}
				?>			  
			  <table width="100%" border="0" cellpadding="0" cellspacing="0" bordercolor="#E7E7E3" background="Iconos/Fondo_PlomoClaro.jpg" bgcolor="#E7E7E3">
				<tr>
				  <td height="42"  bgcolor="#000066"><p align="center" class="style4">MUNICIPIO <?php echo $municipio.", "."GESTION ".$lst_gestion;?></p></td>
				</tr>
				<tr>
				  <td height="25" bgcolor="#3399FF"><span class="style4">Total de Cabezas de Ganado</span></td>
			  </tr>
				<tr>
				  <td height="25"><table width="100%" border="0">
					<tr>
					  <td><table width="100%" border="0" cellpadding="0">
						<tr>
						  <td width="8%" height="22" bgcolor="#3399FF"><span class="Estilo15 Estilo17">Nro</span></td>
						  <td width="60%" bgcolor="#3399FF"><span class="Estilo15 Estilo17">Especie</span></td>
						  <td width="32%" bgcolor="#3399FF"><span class="Estilo15 Estilo17">Cantidad</span></td>
						</tr>
						<?php						
						$i=1;$TGanado=0;
						$SQL = "select tipo_especie.tipo_especie, ganado.cod_ganado, ganado.cantidad from ganado, tipo_especie where";
						$SQL .= " ganado.cod_tipo_especie=tipo_especie.cod_tipo_especie and ganado.cod_municipio='$cod_municipio'";
						$SQL .= " and ganado.gestion='$lst_gestion' order by tipo_especie.tipo_especie";
						$resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
						while($row = pg_fetch_array($resultado))
							{$cod_ganado=$row["cod_ganado"];?>
						<tr>
						  <td bgcolor="#CCCCCC" ><span class="Estilo18"><?php echo $i;?></span></td>
						  <td bgcolor="#CCCCCC" ><span class="Estilo18"><?php echo $row["tipo_especie"];?></span></td>
						  <?php $TGanado=$TGanado+$row["cantidad"];?>
						  <td bgcolor="#CCCCCC" ><span class="Estilo18"><?php echo number_format($row["cantidad"],2,",",".");?></span></td>
						</tr>
						<?php $i++;}?>
						<tr>
						  <td bgcolor="#999999" ><span class="Estilo18"></span></td>
						  <td bgcolor="#999999" ><span class="Estilo18"><strong>TOTAL</strong></span></td>
						  <td bgcolor="#999999" ><span class="Estilo18"><strong><?php echo number_format($TGanado,2,",",".");?></strong></span></td>
						</tr>
					  </table></td>
					</tr>
				  </table></td>	  
			  </tr>
			<?php
			if(ActivaProduccion($cod_municipio, 1, $lst_gestion)>0)
				{?>  
				<tr>
				  <td height="25" bgcolor="#FF3333"><span class="style4">Producci&oacute;n de Carne </span></td>
				</tr>
				<tr>
				  <td><table width="100%" border="0">
					<tr>					  
					  <td><table width="100%" border="0">
						<tr>
						  <td width="30%" height="22" bgcolor="#FF3333"><span class="Estilo15 Estilo17">Especie</span></td>
						  <td width="17%" bgcolor="#FF3333"><span class="Estilo15 Estilo17">Poblac.(No. A)</span></td>
						  <td width="17%" bgcolor="#FF3333"><span class="Estilo15 Estilo17">Tasa Extrac.(%) </span></td>
						  <td width="17%" bgcolor="#FF3333"><span class="Estilo15 Estilo17">Rendim.(kg/A)</span></td>
						  <td width="19%" bgcolor="#FF3333"><span class="Estilo15 Estilo17">Produc.(kg)</span></td>
						</tr>
						<?php
						$i=0;
						$SQL = "select * from ppecuaria, tipo_especie, especie where especie.cod_tipo_especie=tipo_especie.cod_tipo_especie";
						$SQL .= " and ppecuaria.cod_especie=especie.cod_especie and ppecuaria.cod_municipio='$cod_municipio' and";
						$SQL .= " ppecuaria.cod_tipo_produccion='1' and ppecuaria.gestion='$lst_gestion' order by tipo_especie.tipo_especie, especie.especie";
						$resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
						while($row = pg_fetch_array($resultado))
							{
							 $Tipo_Especie[$i]=$row["tipo_especie"];
							 $i++;
							}
						$i=0;$j=0;$sw=0; 
						$SQL = "select * from ppecuaria, tipo_especie, especie where especie.cod_tipo_especie=tipo_especie.cod_tipo_especie";
						$SQL .= " and ppecuaria.cod_especie=especie.cod_especie and ppecuaria.cod_municipio='$cod_municipio' and";
						$SQL .= " ppecuaria.cod_tipo_produccion='1' and ppecuaria.gestion='$lst_gestion' order by tipo_especie.tipo_especie, especie.especie";
						$resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
						while($row = pg_fetch_array($resultado))
							{
							 $cod_ppecuaria=$row["cod_ppecuaria"];
							 if($sw==0)
									  {$tipo_especie=$row["tipo_especie"];?>						  
									   <tr>
									   <td bgcolor="#999999"><span class="Estilo18"><?php echo $row["tipo_especie"];?></span></td>
									   <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalPoblacion1($tipo_especie, $cod_municipio, 1, $lst_gestion),2,",",".");?></span></td>
									   <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalTasaExtraccion1($tipo_especie, $cod_municipio, 1, $lst_gestion),2,",",".");?></span></td>
									   <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalRendimiento1($tipo_especie, $cod_municipio, 1, $lst_gestion),2,",",".");?></span></td>
									   <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalProduccion1($tipo_especie, $cod_municipio, 1, $lst_gestion),2,",",".");?></span></td>
									   </tr>
								 <?php $sw=1;
									  }				
							 if($row["tipo_especie"]==$Tipo_Especie[$i])
												  {$b=1;}
											  else
												  {
												   if($row["tipo_especie"]==$Tipo_Especie[$i+1])
															 {$tipo_especie=$row["tipo_especie"];?>
															  <tr>
															  <td bgcolor="#999999"><span class="Estilo18"><?php echo $row["tipo_especie"];?></span></div></td>
															  <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalPoblacion1($tipo_especie, $cod_municipio, 1, $lst_gestion),2,",",".");?></span></td>
															  <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalTasaExtraccion1($tipo_especie, $cod_municipio, 1, $lst_gestion),2,",",".");?></span></td>
															  <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalRendimiento1($tipo_especie, $cod_municipio, 1, $lst_gestion),2,",",".");?></span></td>
															  <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalProduccion1($tipo_especie, $cod_municipio, 1, $lst_gestion),2,",",".");?></span></td>
															  </tr>
													   <?php }
												 }
										if($b==1)
												 {?>
												  <tr>
												  <td><span class="Estilo18"><?php echo $row["especie"];?></span></div></td>
												  <td><span class="Estilo18"><?php echo number_format($row["poblacion"],2,",",".");?></span></td>
												  <td><span class="Estilo18"><?php echo number_format($row["tasaextraccion"],2,",",".");?></span></td>
												  <td><span class="Estilo18"><?php echo number_format($row["rendimiento"],2,",",".");?></span></td>
												  <td><span class="Estilo18"><?php echo number_format($row["produccion"],2,",",".");?></span></td>
												  </tr>
											<?php $j++;$i=$j-1;
												 }
						}?>				 		  
					  </table></td>
					</tr>
				  </table></td>
				</tr>    
				<tr>
				  <td><div align="justify"><hr align="center"></div></td>
				</tr>
			<?php }?>	  
			<!-- Produccion Cuero -->
			<?php
			if(ActivaProduccion($cod_municipio, 2, $lst_gestion)>0)
				{?>
			  <tr>
				<td height="25" bgcolor="#66CCCC"><span class="style4">Producci&oacute;n de Cuero</span></td>
			  </tr>
				<tr>
				  <td><table width="100%" border="0">
					<tr>
					  <td><table width="100%" border="0">
						<tr>
						  <td width="30%" height="22" bgcolor="#66CCCC"><span class="Estilo15 Estilo17">Especie</span></td>
						  <td width="22%" bgcolor="#66CCCC"><span class="Estilo15 Estilo17">Poblac.(No. A)</span></td>
						  <td width="22%" bgcolor="#66CCCC"><span class="Estilo15 Estilo17">Tasa Extrac.(%) </span></td>
						  <td width="23%" bgcolor="#66CCCC"><span class="Estilo15 Estilo17">Produc.(kg)</span></td>
						</tr>
						<?php						
						$i=0;
						$SQL = "select * from ppecuaria, tipo_especie, especie where especie.cod_tipo_especie=tipo_especie.cod_tipo_especie";
						$SQL .= " and ppecuaria.cod_especie=especie.cod_especie and ppecuaria.cod_municipio='$cod_municipio' and";
						$SQL .= " ppecuaria.cod_tipo_produccion='2' and ppecuaria.gestion='$lst_gestion' order by tipo_especie.tipo_especie, especie.especie";
						$resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
						while($row = pg_fetch_array($resultado))
							{
							 $Tipo_Especie[$i]=$row["tipo_especie"];
							 $i++;
							}
						$i=0;$j=0;$sw=0; 
						$SQL = "select * from ppecuaria, tipo_especie, especie where especie.cod_tipo_especie=tipo_especie.cod_tipo_especie";
						$SQL .= " and ppecuaria.cod_especie=especie.cod_especie and ppecuaria.cod_municipio='$cod_municipio' and";
						$SQL .= " ppecuaria.cod_tipo_produccion='2' and ppecuaria.gestion='$lst_gestion' order by tipo_especie.tipo_especie, especie.especie";
						$resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
						while($row = pg_fetch_array($resultado))
							{
							 $cod_ppecuaria=$row["cod_ppecuaria"];
							 if($sw==0)
									  {$tipo_especie=$row["tipo_especie"];?>						  
									   <tr>
									   <td bgcolor="#999999"><span class="Estilo18"><?php echo $row["tipo_especie"];?></span></div></td>
									   <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalPoblacion1($tipo_especie, $cod_municipio, 2, $lst_gestion),2,",",".");?></span></td>
									   <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalTasaExtraccion1($tipo_especie, $cod_municipio, 2, $lst_gestion),2,",",".");?></span></td>
									   <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalProduccion1($tipo_especie, $cod_municipio, 2, $lst_gestion),2,",",".");?></span></td>
									   </tr>
								 <?php $sw=1;
									  }				
							 if($row["tipo_especie"]==$Tipo_Especie[$i])
												  {$b=1;}
											  else
												  {
												   if($row["tipo_especie"]==$Tipo_Especie[$i+1])
															 {$tipo_especie=$row["tipo_especie"];?>
															  <tr>
															  <td bgcolor="#999999"><span class="Estilo18"><?php echo $row["tipo_especie"];?></span></div></td>
															  <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalPoblacion1($tipo_especie, $cod_municipio, 2, $lst_gestion),2,",",".");?></span></td>
															  <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalTasaExtraccion1($tipo_especie, $cod_municipio, 2, $lst_gestion),2,",",".");?></span></td>
															  <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalProduccion1($tipo_especie, $cod_municipio, 2, $lst_gestion),2,",",".");?></span></td>
															  </tr>
													   <?php }
												 }
										if($b==1)
												 {?>
												  <tr>
												  <td><span class="Estilo18"><?php echo $row["especie"];?></span></td>
												  <td><span class="Estilo18"><?php echo number_format($row["poblacion"],2,",",".");?></span></td>
												  <td><span class="Estilo18"><?php echo number_format($row["tasaextraccion"],2,",",".");?></span></td>
												  <td><span class="Estilo18"><?php echo number_format($row["produccion"],2,",",".");?></span></td>
												  </tr>
											<?php $j++;$i=$j-1;
												 }
						}?>
					  </table></td>
					</tr>
				  </table></td>
				</tr>    
					
				<tr>
				  <td><div align="justify"><hr align="center"></div></td>
				</tr>	
			<?php }?>
			<!-- Produccion Fibra -->
			<?php
			if(ActivaProduccion($cod_municipio, 3, $lst_gestion)>0)
				{?>
			<tr>
				<td height="25" bgcolor="#9966CC"><span class="style4">Producci&oacute;n de Fibra </span></td>
			  </tr>
				<tr>
				  <td><table width="100%" border="0">
					<tr>
					  <td><table width="100%" border="0">
						<tr>
						  <td width="30%" height="22" bgcolor="#9966CC"><span class="Estilo15 Estilo17">Especie</span></td>
						  <td width="17%" bgcolor="#9966CC"><span class="Estilo15 Estilo17">Poblac.(No. A)</span></td>
						  <td width="17%" bgcolor="#9966CC"><span class="Estilo15 Estilo17">Tasa Extrac.(%) </span></td>
						  <td width="17%" bgcolor="#9966CC"><span class="Estilo15 Estilo17">Rendim.(kg/A)</span></td>
						  <td width="19%" bgcolor="#9966CC"><span class="Estilo15 Estilo17">Produc.(kg)</span></td>
						</tr>
						<?php						
						$i=0;
						$SQL = "select * from ppecuaria, tipo_especie, especie where especie.cod_tipo_especie=tipo_especie.cod_tipo_especie";
						$SQL .= " and ppecuaria.cod_especie=especie.cod_especie and ppecuaria.cod_municipio='$cod_municipio' and";
						$SQL .= " ppecuaria.cod_tipo_produccion='3' and ppecuaria.gestion='$lst_gestion' order by tipo_especie.tipo_especie, especie.especie";
						$resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
						while($row = pg_fetch_array($resultado))
							{
							 $Tipo_Especie[$i]=$row["tipo_especie"];
							 $i++;
							}
						$i=0;$j=0;$sw=0; 
						$SQL = "select * from ppecuaria, tipo_especie, especie where especie.cod_tipo_especie=tipo_especie.cod_tipo_especie";
						$SQL .= " and ppecuaria.cod_especie=especie.cod_especie and ppecuaria.cod_municipio='$cod_municipio' and";
						$SQL .= " ppecuaria.cod_tipo_produccion='3' and ppecuaria.gestion='$lst_gestion' order by tipo_especie.tipo_especie, especie.especie";
						$resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
						while($row = pg_fetch_array($resultado))
							{
							 $cod_ppecuaria=$row["cod_ppecuaria"];
							 if($sw==0)
									  {$tipo_especie=$row["tipo_especie"];?>						  
									   <tr>
									   <td bgcolor="#999999"><span class="Estilo18"><?php echo $row["tipo_especie"];?></span></div></td>
									   <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalPoblacion1($tipo_especie, $cod_municipio, 3, $lst_gestion),2,",",".");?></span></td>
									   <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalTasaExtraccion1($tipo_especie, $cod_municipio, 3, $lst_gestion),2,",",".");?></span></td>
									   <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalRendimiento1($tipo_especie, $cod_municipio, 3, $lst_gestion),2,",",".");?></span></td>
									   <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalProduccion1($tipo_especie, $cod_municipio, 3, $lst_gestion),2,",",".");?></span></td>
									   </tr>
								 <?php $sw=1;
									  }				
							 if($row["tipo_especie"]==$Tipo_Especie[$i])
												  {$b=1;}
											  else
												  {
												   if($row["tipo_especie"]==$Tipo_Especie[$i+1])
															 {$tipo_especie=$row["tipo_especie"];?>
															  <tr>
															  <td bgcolor="#999999"><span class="Estilo18"><?php echo $row["tipo_especie"];?></span></div></td>
															  <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalPoblacion1($tipo_especie, $cod_municipio, 3, $lst_gestion),2,",",".");?></span></td>
															  <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalTasaExtraccion1($tipo_especie, $cod_municipio, 3, $lst_gestion),2,",",".");?></span></td>
															  <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalRendimiento1($tipo_especie, $cod_municipio, 3, $lst_gestion),2,",",".");?></span></td>
															  <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalProduccion1($tipo_especie, $cod_municipio, 3, $lst_gestion),2,",",".");?></span></td>
															  </tr>
													   <?php }
												 }
										if($b==1)
												 {?>
												  <tr>
												  <td><span class="Estilo18"><?php echo $row["especie"];?></span></div></td>
												  <td><span class="Estilo18"><?php echo number_format($row["poblacion"],2,",",".");?></span></td>
												  <td><span class="Estilo18"><?php echo number_format($row["tasaextraccion"],2,",",".");?></span></td>
												  <td><span class="Estilo18"><?php echo number_format($row["rendimiento"],2,",",".");?></span></td>
												  <td><span class="Estilo18"><?php echo number_format($row["produccion"],2,",",".");?></span></td>
												  </tr>
											<?php $j++;$i=$j-1;
												 }
						}?>				 		  
					  </table></td>
					</tr>
				  </table></td>
				</tr>    
					
				<tr>
				  <td><div align="justify"><hr align="center"></div></td>
				</tr>		
			<?php }?>
			<!-- Produccion Huevo -->
			<?php
			if(ActivaProduccion($cod_municipio, 4, $lst_gestion)>0)
				{?>
			<tr>
				<td height="25" bgcolor="#0099FF"><span class="style4">Producci&oacute;n de Huevo </span></td>
			  </tr>
				<tr>
				  <td><table width="100%" border="0">
					<tr>
					  <td><table width="100%" border="0">
						<tr>
						  <td width="30%" height="22" bgcolor="#0099FF"><span class="Estilo15 Estilo17">Especie</span></td>
						  <td width="22%" bgcolor="#0099FF"><span class="Estilo15 Estilo17">Poblac.(No. A)</span></td>
						  <td width="22%" bgcolor="#0099FF"><span class="Estilo15 Estilo17">Rend. Postura(%) </span></td>
						  <td width="26%" bgcolor="#0099FF"><span class="Estilo15 Estilo17">Produc.(Un)</span></td>
						</tr>
						<?php						
						$i=0;
						$SQL = "select * from ppecuaria, tipo_especie, especie where especie.cod_tipo_especie=tipo_especie.cod_tipo_especie";
						$SQL .= " and ppecuaria.cod_especie=especie.cod_especie and ppecuaria.cod_municipio='$cod_municipio' and";
						$SQL .= " ppecuaria.cod_tipo_produccion='4' and ppecuaria.gestion='$lst_gestion' order by tipo_especie.tipo_especie, especie.especie";
						$resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
						while($row = pg_fetch_array($resultado))
							{
							 $Tipo_Especie[$i]=$row["tipo_especie"];
							 $i++;
							}
						$i=0;$j=0;$sw=0; 
						$SQL = "select * from ppecuaria, tipo_especie, especie where especie.cod_tipo_especie=tipo_especie.cod_tipo_especie";
						$SQL .= " and ppecuaria.cod_especie=especie.cod_especie and ppecuaria.cod_municipio='$cod_municipio' and";
						$SQL .= " ppecuaria.cod_tipo_produccion='4' and ppecuaria.gestion='$lst_gestion' order by tipo_especie.tipo_especie, especie.especie";
						$resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
						while($row = pg_fetch_array($resultado))
							{
							 $cod_ppecuaria=$row["cod_ppecuaria"];
							 if($sw==0)
									  {$tipo_especie=$row["tipo_especie"];?>						  
									   <tr>
									   <td bgcolor="#999999"><span class="Estilo18"><?php echo $row["tipo_especie"];?></span></div></td>
									   <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalPoblacion1($tipo_especie, $cod_municipio, 4, $lst_gestion),2,",",".");?></span></td>
									   <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalTasaExtraccion1($tipo_especie, $cod_municipio, 4, $lst_gestion),2,",",".");?></span></td>
									   <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalProduccion1($tipo_especie, $cod_municipio, 4, $lst_gestion),2,",",".");?></span></td>
									   </tr>
								 <?php $sw=1;
									  }				
							 if($row["tipo_especie"]==$Tipo_Especie[$i])
												  {$b=1;}
											  else
												  {
												   if($row["tipo_especie"]==$Tipo_Especie[$i+1])
															 {$tipo_especie=$row["tipo_especie"];?>
															  <tr>
															  <td bgcolor="#999999"><span class="Estilo18"><?php echo $row["tipo_especie"];?></span></div></td>
															  <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalPoblacion1($tipo_especie, $cod_municipio, 4, $lst_gestion),2,",",".");?></span></td>
															  <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalTasaExtraccion1($tipo_especie, $cod_municipio, 4, $lst_gestion),2,",",".");?></span></td>												 
															  <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalProduccion1($tipo_especie, $cod_municipio, 4, $lst_gestion),2,",",".");?></span></td>
															  </tr>
													   <?php }
												 }
										if($b==1)
												 {?>
												  <tr>
												  <td><span class="Estilo18"><?php echo $row["especie"];?></span></div></td>
												  <td><span class="Estilo18"><?php echo number_format($row["poblacion"],2,",",".");?></span></td>
												  <td><span class="Estilo18"><?php echo number_format($row["tasaextraccion"],2,",",".");?></span></td>
												  <td><span class="Estilo18"><?php echo number_format($row["produccion"],2,",",".");?></span></td>
												  </tr>
											<?php $j++;$i=$j-1;
												 }
						}?>
					  </table></td>
					</tr>
				  </table></td>
				</tr>    
					
				<tr>
				  <td><div align="justify"><hr align="center"></div></td>
				</tr>	
			<?php }?>	
			<!-- Produccion Leche -->
			<?php
			if(ActivaProduccion($cod_municipio, 5, $lst_gestion)>0)
				{?>
			<tr>
				<td height="25" bgcolor="#FF6600"><span class="style4">Producci&oacute;n de Leche </span></td>
			  </tr>
				<tr>
				  <td><table width="100%" border="0">
					<tr>
					  <td><table width="100%" border="0">
						<tr>
						  <td width="30%" height="22" bgcolor="#FF6600"><span class="Estilo15 Estilo17">Especie</span></td>
						  <td width="22%" bgcolor="#FF6600"><span class="Estilo15 Estilo17">Poblac.(No. A)</span></td>
						  <td width="22%" bgcolor="#FF6600"><span class="Estilo15 Estilo17">Vacas Prod.(No.A) </span></td>
						  <td width="26%" bgcolor="#FF6600"><span class="Estilo15 Estilo17">Rendim.(kg/A)</span></td>
						</tr>
						<?php						
						$i=0;
						$SQL = "select * from ppecuaria, tipo_especie, especie where especie.cod_tipo_especie=tipo_especie.cod_tipo_especie";
						$SQL .= " and ppecuaria.cod_especie=especie.cod_especie and ppecuaria.cod_municipio='$cod_municipio' and";
						$SQL .= " ppecuaria.cod_tipo_produccion='5' and ppecuaria.gestion='$lst_gestion' order by tipo_especie.tipo_especie, especie.especie";
						$resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
						while($row = pg_fetch_array($resultado))
							{
							 $Tipo_Especie[$i]=$row["tipo_especie"];
							 $i++;
							}
						$i=0;$j=0;$sw=0; 
						$SQL = "select * from ppecuaria, tipo_especie, especie where especie.cod_tipo_especie=tipo_especie.cod_tipo_especie";
						$SQL .= " and ppecuaria.cod_especie=especie.cod_especie and ppecuaria.cod_municipio='$cod_municipio' and";
						$SQL .= " ppecuaria.cod_tipo_produccion='5' and ppecuaria.gestion='$lst_gestion' order by tipo_especie.tipo_especie, especie.especie";
						$resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
						while($row = pg_fetch_array($resultado))
							{
							 $cod_ppecuaria=$row["cod_ppecuaria"];
							 if($sw==0)
									  {$tipo_especie=$row["tipo_especie"];?>						  
									   <tr>
									   <td bgcolor="#999999"><span class="Estilo18"><?php echo $row["tipo_especie"];?></span></div></td>
									   <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalPoblacion1($tipo_especie, $cod_municipio, 5, $lst_gestion),2,",",".");?></span></td>
									   <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalRendimiento1($tipo_especie, $cod_municipio, 5, $lst_gestion),2,",",".");?></span></td>
									   <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalProduccion1($tipo_especie, $cod_municipio, 5, $lst_gestion),2,",",".");?></span></td>
									   </tr>
								 <?php $sw=1;
									  }				
							 if($row["tipo_especie"]==$Tipo_Especie[$i])
												  {$b=1;}
											  else
												  {
												   if($row["tipo_especie"]==$Tipo_Especie[$i+1])
															 {$tipo_especie=$row["tipo_especie"];?>
															  <tr>
															  <td bgcolor="#999999"><span class="Estilo18"><?php echo $row["tipo_especie"];?></span></div></td>
															  <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalPoblacion1($tipo_especie, $cod_municipio, 5, $lst_gestion),2,",",".");?></span></td>
															  <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalRendimiento1($tipo_especie, $cod_municipio, 5, $lst_gestion),2,",",".");?></span></td>
															  <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalProduccion1($tipo_especie, $cod_municipio, 5, $lst_gestion),2,",",".");?></span></td>
															  </tr>
													   <?php }
												 }
										if($b==1)
												 {?>
												  <tr>
												  <td><span class="Estilo18"><?php echo $row["especie"];?></span></div></td>
												  <td><span class="Estilo18"><?php echo number_format($row["poblacion"],2,",",".");?></span></td>
												  <td><span class="Estilo18"><?php echo number_format($row["rendimiento"],2,",",".");?></span></td>
												  <td><span class="Estilo18"><?php echo number_format($row["produccion"],2,",",".");?></span></td>
												  </tr>
											<?php $j++;$i=$j-1;
												 }
						}?>				 		  
					  </table></td>
					</tr>
				  </table></td>
				</tr>    
			<?php }?>	
				<tr>
				  <td height="25" bgcolor="#666666">&nbsp;</td>
				</tr>
				<tr>
				  <td height="25"><span class="Estilo15 Estilo17">Fuente.: <?php echo $fuentep." ,".$lst_gestion."; Nota.:".$observaciones ; ?></span></td>
				</tr>
				<tr>
				  <td><div align="justify"><hr align="center"></div></td>
				</tr>	
				<tr>
				  <td><table width="100%" border="0">
				  </table></td>
				</tr>
			  </table>
			  <?php }?> 
			  </div>
              <div id="cpestana4">
			  <?php 
			    $SQL = "select * from fuenteep where cod_municipio='$cod_municipio' and gestion='$lst_gestion'";
				$resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
				$row = pg_fetch_array($resultado);
				$gestion=$row["gestion"];
				$fuenteep=$row["fuenteep"];
				$observaciones=$row["observaciones"];
				function TotalActividad($tipo_actividad, $cod_municipio, $lst_gestion)
				{
				  $conexion = conectar();  
				  $SQL = "select sum(pprogramado.monto) as monto from pprogramado, tipo_actividad, actividad where pprogramado.cod_actividad=actividad.cod_actividad";
				  $SQL .= " and actividad.cod_tipo_actividad=tipo_actividad.cod_tipo_actividad and tipo_actividad.tipo_actividad='$tipo_actividad'"; 
				  $SQL .= " and pprogramado.cod_municipio='$cod_municipio' and pprogramado.gestion='$lst_gestion' group by tipo_actividad.tipo_actividad";
				  $SQL .= " order by tipo_actividad.tipo_actividad";
				  $resultado = pg_query($conexion, $SQL);
				  $row = pg_fetch_array($resultado);
				  $monto=$row["monto"];
				  return($monto);		
				}
				function ActivaEP($cod_municipio, $cod_programa, $lst_gestion)
				{
				 $conexion = conectar();
				 $SQL = "select monto from epresupuestaria, presupuesto where epresupuestaria.cod_presupuesto=presupuesto.cod_presupuesto";
				 $SQL .= " and epresupuestaria.cod_municipio='$cod_municipio' and epresupuestaria.gestion='$lst_gestion' and";
				 $SQL .= " epresupuestaria.cod_programa='$cod_programa' order by presupuesto.cod_presupuesto";
				 $resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
				 if($row = pg_fetch_array($resultado))
						{return(1);}
					else 
						{return(0);}
				}
				function ActivaPP($cod_municipio, $lst_gestion)
				{
				 $conexion = conectar();
				 $SQL = "select * from pprogramado, tipo_actividad, actividad where actividad.cod_tipo_actividad=tipo_actividad.cod_tipo_actividad";
				 $SQL .= " and pprogramado.cod_actividad=actividad.cod_actividad and pprogramado.cod_municipio='$cod_municipio' and";
				 $SQL .= " pprogramado.gestion='$lst_gestion' order by tipo_actividad.tipo_actividad, actividad.actividad";
				 $resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
				 if($row = pg_fetch_array($resultado))
						{return(1);}
					else 
						{return(0);}
				}
				?>
			  <table width="100%" border="0" cellpadding="0" cellspacing="0" bordercolor="#E7E7E3" background="Iconos/Fondo_PlomoClaro.jpg" bgcolor="#E7E7E3">
				<tr>
				  <?php $AGestion=$lst_gestion-1;?>
				  <td height="42"  bgcolor="#000066"><p align="center" class="style4">MUNICIPIO <?php echo $municipio.", "."GESTION ".$lst_gestion;?></p>        </td>
				</tr>
				<tr>
				  <td><div align="justify"><hr align="center"></div></td>
				</tr>
				<tr>
				  <td height="25" bgcolor="#669999"><span class="style4">Ejecuci&oacute;n Presupuestaria Gesti&oacute;n del Riesgo - <?php echo $AGestion;?></span></td>
			  </tr>
				<tr>
				  <td><table width="100%" border="0">
					<tr>					  
					  <td><table width="100%" border="0">
						  <tr>
							<td width="25%" height="22" bgcolor="#669999"><span class="Estilo15 Estilo17">Presupuesto Vigente</span></td>
							<td width="25%" bgcolor="#669999"><span class="Estilo15 Estilo17">Ejecutado</span></td>
							<td width="25%" bgcolor="#669999"><span class="Estilo15 Estilo17">Saldos</span></td>
							<td width="25%" bgcolor="#669999"><span class="Estilo15 Estilo17">% Ejecuci&oacute;n</span></td>
						  </tr>
						  <?php						
						$i=1;$TGanado=0;
						$SQL = "select * from apresupuesto where cod_municipio='$cod_municipio' and gestion='$lst_gestion' order by agestion";
						$resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
						while($row = pg_fetch_array($resultado))
							{$cod_apresupuesto=$row["cod_apresupuesto"];?>
						  <tr>
							<td ><span class="Estilo18"><?php echo number_format($row["vigente"],2,",",".");?></span></td>
							<td ><span class="Estilo18"><?php echo number_format($row["ejecutado"],2,",",".");?></span></td>
							<?php $Saldo=$row["vigente"]-$row["ejecutado"];$por=($row["ejecutado"]*100)/$row["vigente"];?>
							<td ><span class="Estilo18"><?php echo number_format($Saldo,2,",",".");?></span></td>				
							<td ><span class="Estilo18"><?php echo number_format($por,2,",",".");?></span></td>
						  </tr>
						  <?php $i++;}?>
					  </table></td>
					</tr>
				  </table></td>
				</tr>
			<?php 	
			if(ActivaEP($cod_municipio, 1, $lst_gestion)>0)
			{?>	  
				<tr>
				  <td height="25" bgcolor="#CC9966"><span class="style4">Ejecuci&oacute;n Presupuestaria Gesti&oacute;n del Riesgo <?php echo $lst_gestion; ?>- Programa 31 (Bs) </span></td>
				</tr>
				<tr>
				  <td><table width="100%" border="0">
					<tr>
					  <td><table width="100%" border="0">
						<tr>
						  <td width="75%" bgcolor="#CC9966"><span class="Estilo15 Estilo17">Ejecuci&oacute;n Presupuestaria</span></td>
						  <td width="25%" bgcolor="#CC9966"><span class="Estilo15 Estilo17">Valor</span></td>
						</tr>
						<?php
						$SQL = "select * from epresupuestaria, presupuesto where epresupuestaria.cod_presupuesto=presupuesto.cod_presupuesto";
						$SQL .= " and epresupuestaria.cod_municipio='$cod_municipio' and epresupuestaria.gestion='$lst_gestion' and";
						$SQL .= " epresupuestaria.cod_programa='1' order by presupuesto.cod_presupuesto";
						$resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
						while($row = pg_fetch_array($resultado))
							{$cod_epresupuestaria=$row["cod_epresupuestaria"];?>
							 <tr>
							 <td><span class="Estilo18"><?php echo $row["presupuesto"];?></span></td>
							 <td><span class="Estilo18"><?php echo number_format($row["monto"],2,",",".");?></span></td>
							 </tr>			
					  <?php }?>			
					  </table></td>
					</tr>
				  </table></td>
				</tr>
			<?php }
			if(ActivaEP($cod_municipio, 2, $lst_gestion)>0)
			{?>
			<tr>
				<td height="25" bgcolor="#9966FF"><span class="style4">Ejecuci&oacute;n Presupuestaria Gesti&oacute;n del Riesgo <?php echo $lst_gestion; ?>- Programa 10 (Bs) </span></td>
			  </tr>
				<tr>
				  <td><table width="100%" border="0">
					<tr>
					  <td><table width="100%" border="0">
						<tr>
						  <td width="75%" bgcolor="#9966FF"><span class="Estilo15 Estilo17">Ejecuci&oacute;n Presupuestaria</span></td>
						  <td width="25%" bgcolor="#9966FF"><span class="Estilo15 Estilo17">Valor</span></td>
						</tr>
						<?php
						$SQL = "select * from epresupuestaria, presupuesto where epresupuestaria.cod_presupuesto=presupuesto.cod_presupuesto";
						$SQL .= " and epresupuestaria.cod_municipio='$cod_municipio' and epresupuestaria.gestion='$lst_gestion' and";
						$SQL .= " epresupuestaria.cod_programa='2' order by presupuesto.cod_presupuesto";
						$resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
						while($row = pg_fetch_array($resultado))
							{$cod_epresupuestaria=$row["cod_epresupuestaria"];?>
							 <tr>
							 <td><span class="Estilo18"><?php echo $row["presupuesto"];?></span></td>
							 <td><span class="Estilo18"><?php echo number_format($row["monto"],2,",",".");?></span></td>
							 </tr>			
					  <?php }?>
					  </table></td>
					</tr>
				  </table></td>
				</tr>    
			<?php }
			if(ActivaPP($cod_municipio, $lst_gestion)>0)
			{?>
			<tr>
				<td height="25" bgcolor="#3399CC"><span class="style4">Presupuesto Programado <?php echo $lst_gestion+1; ?></span></td>
			  </tr>
				<tr>
				  <td><table width="100%" border="0">
					<tr>
					  <td><table width="100%" border="0">
						<tr>
						  <td width="80%" bgcolor="#3399CC"><span class="Estilo15 Estilo17">Tipo de Gasto</span></td>
						  <td width="13%" bgcolor="#3399CC"><span class="Estilo15 Estilo17">Bs.</span></td>
						  <td width="7%" bgcolor="#3399CC"><span class="Estilo15 Estilo17">%</span></td>
						</tr>
						<?php						
						$i=0;
						$SQL = "select * from pprogramado, tipo_actividad, actividad where actividad.cod_tipo_actividad=tipo_actividad.cod_tipo_actividad";
						$SQL .= " and pprogramado.cod_actividad=actividad.cod_actividad and pprogramado.cod_municipio='$cod_municipio' and";
						$SQL .= " pprogramado.gestion='$lst_gestion' order by tipo_actividad.tipo_actividad, actividad.actividad";
						$resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
						while($row = pg_fetch_array($resultado))
							{
							 $Tipo_Actividad[$i]=$row["tipo_actividad"];
							 $i++;
							}
						$i=0;$j=0;$sw=0;$por=0;$TPor=0; 
						$SQL = "select * from pprogramado, tipo_actividad, actividad where actividad.cod_tipo_actividad=tipo_actividad.cod_tipo_actividad";
						$SQL .= " and pprogramado.cod_actividad=actividad.cod_actividad and pprogramado.cod_municipio='$cod_municipio' and";
						$SQL .= " pprogramado.gestion='$lst_gestion' order by tipo_actividad.tipo_actividad, actividad.actividad";
						$resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
						while($row = pg_fetch_array($resultado))
							{
							 $cod_pprogramado=$row["cod_pprogramado"];
							 if($sw==0)
									  {$tipo_actividad=$row["tipo_actividad"];$TPor=(100*TotalActividad($tipo_actividad, $cod_municipio, $lst_gestion))/TotalActividad($tipo_actividad, $cod_municipio, $lst_gestion);?>
									   <tr>
									   <td bgcolor="#999999"><span class="Estilo18"><?php echo $row["tipo_actividad"];?></span></td>
									   <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalActividad($tipo_actividad, $cod_municipio, $lst_gestion),2,",",".");?></span></td>
									   <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format($TPor,2,",",".");?></span></td>
									   </tr>
								 <?php $sw=1;
									  }				
							 if($row["tipo_actividad"]==$Tipo_Actividad[$i])
												  {$b=1;}
											  else
												  {
												   if($row["tipo_actividad"]==$Tipo_Actividad[$i+1])
															 {$tipo_actividad=$row["tipo_actividad"];$TPor=(100*TotalActividad($tipo_actividad, $cod_municipio, $lst_gestion))/TotalActividad($tipo_actividad, $cod_municipio, $lst_gestion);?>
															  <tr>
															  <td bgcolor="#999999"><span class="Estilo18"><?php echo $row["tipo_actividad"];?></span></td>
															  <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format(TotalActividad($tipo_actividad, $cod_municipio, $lst_gestion),2,",",".");?></span></td>
															  <td bgcolor="#999999"><span class="Estilo18"><?php echo number_format($TPor,2,",",".");?></span></td>
															  </tr>
													   <?php }
												 }
										if($b==1)
												 {?>
												  <tr>
												  <?php $por=($row["monto"]*100)/TotalActividad($tipo_actividad, $cod_municipio, $lst_gestion);?> 
												  <td><span class="Estilo18"><?php echo $row["actividad"];?></span></td>
												  <td><span class="Estilo18"><?php echo number_format($row["monto"],2,",",".");?></span></td>
												  <td><span class="Estilo18"><?php echo number_format($por,2,",",".");?></span></td>
												  </tr>
											<?php $j++;$i=$j-1;
												 }
						}?>
					  </table></td>
					</tr>
				  </table></td>
				</tr>    
				<tr>
				  <td height="25" bgcolor="#666666">&nbsp;</td>
				</tr>
				<tr>
				  <td height="25" bgcolor="#999999"><span class="Estilo15 Estilo17">Fuente.: <?php echo $fuenteep." ,".$gestion.", Nota.:".$observaciones; ?></span></td>
				</tr>
			<?php }?>		
			</table>			  
			  </div>
              <div id="cpestana5"> 
			  <?php
			  if(($lst_gestion!="")&&($cod_municipio!=""))
				{
			  $SQL = "select * from fuentegra where cod_municipio='$cod_municipio' and gestion='$lst_gestion'";
			  $resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
			  $row = pg_fetch_array($resultado);
			  $gestion=$row["gestion"];
			  $fuentegra=$row["fuentegra"];
			  $planificacion=$row["planificacion"];
			  $observaciones=$row["observaciones"];
			  ?> 
			  <table width="100%" border="0" cellpadding="0" cellspacing="0" bordercolor="#E7E7E3" background="Iconos/Fondo_PlomoClaro.jpg" bgcolor="#E7E7E3">
				<tr>
				  <td height="42"  bgcolor="#000066"><p align="center" class="style4">MUNICIPIO <?php echo $municipio.", "."GESTION ".$lst_gestion;?></p></td>
				</tr>
				<tr>
				  <td><div align="justify"><hr align="center"></div></td>
				</tr>
				
				<tr>
				  <td height="25" bgcolor="#3399FF"><span class="style4">Indicadores para la Gesti&oacute;n del Riesgo Agropecurio y Reducci&oacute;n de Desastres </span></td>
				</tr>
				<tr>
				  <td><table width="100%" border="0">
					<tr>
					  <td><table width="100%" border="0">
						<tr>
						  <td width="75%" bgcolor="#3399FF"><span class="Estilo15 Estilo17">Tipo de Evento</span></td>
						  <td width="25%" bgcolor="#3399FF"><span class="Estilo15 Estilo17">Cantidad</span></td>
						</tr>
						<?php
						$TC=0;
						$SQL = "select * from evento, tipo_evento where evento.cod_tipo_evento=tipo_evento.cod_tipo_evento and";
						$SQL .= " evento.cod_municipio='$cod_municipio' and evento.gestion='$lst_gestion' order by tipo_evento.tipo_evento";
						$resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
						while($row = pg_fetch_array($resultado))
							{$cod_evento=$row["cod_evento"];?>				 
							 <tr>
							 <td><span class="Estilo18"><?php echo $row["tipo_evento"];?></span></td>
							 <?php $TC=$TC+$row["cantidad"];?>
							 <td><span class="Estilo18"><?php echo $row["cantidad"];?></span></td>
							 </tr>			
					  <?php }?>
						<tr>
						<td bgcolor="#999999"><span class="Estilo18"><strong>TOTAL</strong></span></td>
						<td bgcolor="#999999"><span class="Estilo18"><strong><?php echo $TC;?></strong></span></td>
						</tr>				 		  
					  </table></td>
					</tr>
				  </table></td>
				</tr>        	
				<tr>
				  <td><div align="justify"><hr align="center"></div></td>
				</tr>	  
			<tr>
				<td height="25" bgcolor="#FF6633"><span class="style4">Serie Historica, Decretos Supremos de Emergencias y Desastres que Involucran al Municipio </span></td>
			  </tr>
				<tr>
				  <td><table width="100%" border="0">
					<tr>
					  <td><table width="100%" border="0">
						<tr>
						  <td width="88%" bgcolor="#FF6633"><span class="Estilo15 Estilo17">Tipo de Emergencia</span></td>
						  <td width="15%" bgcolor="#FF6633"><span class="Estilo15 Estilo17">Valor</span></td>
						</tr>
						<?php						
						$SQL = "select * from emergencia, tipo_emergencia where emergencia.cod_tipo_emergencia=tipo_emergencia.cod_tipo_emergencia";
						$SQL .= " and emergencia.cod_municipio='$cod_municipio' and emergencia.gestion='$lst_gestion' order by tipo_emergencia.tipo_emergencia";
						$resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
						while($row = pg_fetch_array($resultado))
							{$cod_emergencia=$row["cod_emergencia"];?>
							 <tr>
							 <td><span class="Estilo18"><?php echo $row["tipo_emergencia"];?></span></td>
							 <td><span class="Estilo18"><?php echo $row["valor"];?></span></td>
							 </tr>
					  <?php }?>
					  </table></td>
					</tr>
				  </table></td>
				</tr>    
				<tr>
				  <td><div align="justify"><hr align="center"></div></td>
				</tr>	
			<tr>
				<td height="25" bgcolor="#669999"><span class="style4">Indicadores de Vulnerabilidad </span></td>
			  </tr>
				<tr>
				  <td><table width="100%" border="0">
					<tr>
					  <td><table width="100%" border="0">
						<tr>
						  <td width="80%" bgcolor="#669999"><span class="Estilo15 Estilo17">Tipo de Indicador</span></td>
						  <td width="10%" bgcolor="#669999"><span class="Estilo15 Estilo17">Valor</span></td>
						  <td width="10%" bgcolor="#669999"><span class="Estilo15 Estilo17">Und.</span></td>
						</tr>
						<?php						
						$SQL = "select * from indicadores, tipo_indicador, unidad where indicadores.cod_tipo_indicador=tipo_indicador.cod_tipo_indicador";
						$SQL .= " and indicadores.cod_municipio='$cod_municipio' and indicadores.gestion='$lst_gestion' and";
						$SQL .= " indicadores.cod_unidad=unidad.cod_unidad order by tipo_indicador.tipo_indicador";
						$resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
						while($row = pg_fetch_array($resultado))
							{$cod_indicadores=$row["cod_indicadores"];?>
							 <tr>
							 <td><span class="Estilo18"><?php echo $row["tipo_indicador"];?></span></td>
							 <td><span class="Estilo18"><?php echo $row["valor"];?></span></td>
							 <td><span class="Estilo18"><?php echo $row["unidad"];?></span></td>
							 </tr>
					  <?php }?>
					  </table></td>
					</tr>
				  </table></td>
				</tr>    
				<tr>
				  <td height="25" bgcolor="#336699">&nbsp;</td>
				</tr>	
				<tr>
				  <td height="25" bgcolor="#999999"><span class="Estilo15 Estilo17">Fuente.: <?php echo $fuentegra." ,".$gestion.", "."¿CUENTA CON ALGUNA PLANIFICACION TERRITORIAL? .:".$planificacion."; Nota.:".$observaciones ; ?></span></td>
				</tr>
				<tr>
				  <td><div align="justify"><hr align="center"></div></td>
				</tr>	
				<tr>
				  <td><table width="100%" border="0">
				  </table></td>
				</tr>
			  </table>	
			  <?php }?> 
			  </div>
              <div id="cpestana6"> 
			  <?php 
			  $SQL = "select * from contactos, municipio where contactos.cod_municipio=municipio.cod_municipio and";
			  $SQL .= " contactos.cod_municipio='$cod_municipio' and contactos.gestion='$lst_gestion'"; 
			  $resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
			  $row = pg_fetch_array($resultado);
			  ?>
			  <table width="100%" border="0" cellpadding="0" cellspacing="0" bordercolor="#E7E7E3" background="Iconos/Fondo_PlomoClaro.jpg" bgcolor="#E7E7E3">
				<tr>
				  <td height="41"  bgcolor="#000066"><p align="center" class="style4">DATOS CONTACTO DEL MUNICIPIO <?php echo $row["municipio"];?>, GESTION <?php echo $row["gestion"];?></p>
				  </td>
				</tr>
				<tr>
				  <td><table width="100%" border="0" cellspacing="0" background="Iconos/Fondo_PlomoClaro.jpg">	    
					<tr>
					  <td height="25" colspan="2" bgcolor="#999999">&nbsp;</td>
					</tr>
					<tr>
					  <td width="50%" bgcolor="#CC3366" class="Estilo10"><span class="Estilo15 Estilo17">Alcalde(sa), Posesionado(a).:</span></td>
					  <td width="50%"><span class="Estilo15 Estilo17"><?php echo $row["nombre"];?></span></td>
					</tr>
					<tr>
					  <td bgcolor="#CC3366" class="Estilo13"><span class="Estilo15 Estilo17">Telefono de Contacto.:</span></td>
					  <td><span class="Estilo15 Estilo17"><?php echo $row["telefono"];?></span></td>
					</tr>
					<tr>
					  <td bgcolor="#CC3366" class="Estilo13"><span class="Estilo15 Estilo17">Fax.:</span></td>
					  <td><span class="Estilo15 Estilo17"><?php echo $row["fax"];?></span></td>
					</tr>
					<tr>
					  <td bgcolor="#CC3366" class="Estilo13"><span class="Estilo15 Estilo17">Direcci&oacute;n.:</span></td>
					  <td><span class="Estilo15 Estilo17"><?php echo $row["direccion"];?></span></td>
					</tr>
					<tr>
					  <td bgcolor="#CC3366" class="Estilo13"><span class="Estilo15 Estilo17">Correo Electronico.:</span></td>
					  <td><span class="Estilo15 Estilo17"><?php echo $row["correo"];?></span></td>
					</tr>
					<tr>
					  <td bgcolor="#CC3366" class="Estilo13"><span class="Estilo15 Estilo17">Partido.:</span></td>
					  <td><span class="Estilo15 Estilo17"><?php echo $row["partido"];?></span></td>
					</tr>
				  </table></td>
				</tr>
				<tr>
				  <td height="25" bgcolor="#999999"><span class="Estilo15 Estilo17">Fuente.:<?php echo $row["fuente"].", "; echo $lst_gestion;?></span></td>
				</tr>
				<tr>
				  <td><div align="justify"><hr align="center"></div></td>
				</tr>
			  </table>
			  
			  </div>
              <div id="cpestana7"> 
			  <?php
			  if(($lst_gestion!="")&&($cod_municipio!=""))
				{
			  $SQL = "select * from fuentemdryt where cod_municipio='$cod_municipio' and gestion='$lst_gestion'";
			  $resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
			  $row = pg_fetch_array($resultado);
			  $gestion=$row["gestion"];
			  $fuentemdryt=$row["fuentemdryt"];
			  $cod_fuentemdryt=$row["cod_fuentemdryt"];	
			  $observaciones=$row["observaciones"];
			  function CuentaInstitucion($cod_municipio, $lst_gestion)
			  {
			    $conexion = conectar();  
			    $SQL = "select count(valor) as valor from presenciamdryt where presenciamdryt.cod_municipio='$cod_municipio'";
			    $SQL .= " and presenciamdryt.gestion='$lst_gestion' and presenciamdryt.valor='SI'";
			    $resultado = pg_query($conexion, $SQL);
			    $row = pg_fetch_array($resultado);
			    $valor=$row["valor"];
			    return($valor);
			  }  
			  ?>
			  <table width="100%" border="0" cellpadding="0" cellspacing="0" bordercolor="#E7E7E3" background="Iconos/Fondo_PlomoClaro.jpg" bgcolor="#E7E7E3">
				<tr>
				  <td height="42"  bgcolor="#000066"><p align="center" class="style4">MUNICIPIO <?php echo $municipio.", "."GESTION ".$lst_gestion;?></p>        </td>
				</tr>
				<tr>
				  <td><table width="100%" border="0">
					<tr>
					  <td><table width="100%" border="0">
						<tr>
						  <td width="80%" height="22" bgcolor="#CC3366"><span class="Estilo15 Estilo17">Institucion del MDRyT</span></td>
						  <td width="10%" height="22" bgcolor="#CC3366"><span class="Estilo15 Estilo17">Sigla</span></td>
						  <td width="10%" bgcolor="#CC3366"><span class="Estilo15 Estilo17">Presencia</span></td>
						</tr>
						<?php						
						$SQL = "select * from presenciamdryt, institucionmdryt where presenciamdryt.cod_institucionmdryt=institucionmdryt.cod_institucionmdryt";
						$SQL .= " and presenciamdryt.cod_municipio='$cod_municipio' and presenciamdryt.gestion='$lst_gestion'";
						$SQL .= " order by institucionmdryt.institucionmdryt";
						$resultado = pg_query($conexion, $SQL) or die("Error con la BD".pg_last_error());
						while($row = pg_fetch_array($resultado))
							{
							 $cod_presenciamdryt=$row["cod_presenciamdryt"];?>
							 <tr>
							 <td><span class="Estilo18"><?php echo $row["institucionmdryt"];?></span></td>
							 <td><span class="Estilo18"><?php echo $row["sigla"];?></span></td>
							 <td><span class="Estilo18"><?php echo $row["valor"];?></span></td>
							 </tr>
					 <?php }?>
					  <tr>
						<td bgcolor="#999999"><span class="Estilo18"></span></td>
						<td bgcolor="#999999"><span class="Estilo18"><strong>TOTAL</strong></span></td>
						<td bgcolor="#999999"><span class="Estilo18"><strong><?php echo CuentaInstitucion($cod_municipio, $lst_gestion);?></strong></span></td>
					  </tr>				 		  
					  </table></td>
					</tr>
				  </table></td>
				</tr>    
				<tr>
				  <td height="25" bgcolor="#CC3366">&nbsp;</td>
				</tr>	
				<tr>
				  <td height="25" bgcolor="#999999"><span class="Estilo15 Estilo17">Fuente.: <?php echo $fuentemdryt." ,".$gestion."; Nota.:".$observaciones ; ?></span></td>
				</tr>    <tr>
				  <td><div align="justify"><hr align="center"></div></td>
				</tr>	
				<tr>
				  <td><table width="100%" border="0">
				  </table></td>
				</tr>
			  </table>
			  <?php }?>  			  
			  </div>
			  <div class="Estilo27 Estilo28" id="cpestana8">
			  <?php
			   echo "<center><a href='http://www.tiempo.com/$municipio.htm' target='_blank'>El Tiempo en el Municipio de $municipio</a></center>";
			   ?>
			  </div>
			  <div class="Estilo27 Estilo28" id="cpestana9">
			  <?php
			   $Muni=strtolower($municipio);
			   echo "<center><input type='button' onclick=AbrirIrma('Irma/$Muni.htm'); value='Indice Riesgo Municipal'></center>";
			   ?>
			  </div>
            </div>
          </div>
		 </td>
        </tr>
      </table>
    </form>
     </div>
	</td>
  </tr>
<?php Print_Fooder (); ?>	  
</table>
</body>
</html>
