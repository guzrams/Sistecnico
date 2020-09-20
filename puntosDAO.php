<?php
include_once 'conex.php';
class puntosDao
{
 private $r;
 public function __construct()
 {
  $this->r=array();
 }
 public function grabar($titulo, $cx, $cy)//metodo para grabar a la base de datos
 {
  $con=conex::con();
  $titulo=pg_escape_string($titulo);
  $cx=pg_escape_string($cx);
  $cy=pg_escape_string($cy);
  $SQL = "INSERT INTO puntos(titulo, cx, cy)";
  $SQL .= " values ('".addslashes($titulo)."', '".addslashes($cx)."', '".addslashes($cy)."')";
  $resul=pg_query($con, $SQL);
  if($resul==1)
  			   {return true;}
		   else
		       {return false;}	   
 }
 public function listar_todo($gestion, $cod_municipio, $cod_modulo)
 {
  $con=conex::con();
  if($cod_modulo==1)
  			{
			  $SQL = "select monitoreo.comunidad, monitoreo.cx, monitoreo.cy, date_part('year', monitoreo.inicio) as anio, observador.nombre, observador.apellido,
					  observador.cod_observador, monitoreo.cod_modulo from municipio, monitoreo, observador where municipio.cod_municipio=monitoreo.cod_municipio
					  and municipio.cod_municipio='$cod_municipio' and monitoreo.cod_observador=observador.cod_observador and date_part('year', inicio)='$gestion'
					  group by monitoreo.comunidad, monitoreo.cx, monitoreo.cy, date_part('year', monitoreo.inicio), observador.nombre, observador.apellido,
					  observador.cod_observador, monitoreo.cod_modulo";
			}
  if($cod_modulo==2)
  			{
			  $SQL = "select precipitaciones.comunidad, precipitaciones.cx, precipitaciones.cy, date_part('year', precipitaciones.inicio) as anio, observador.nombre, observador.apellido,
					  observador.cod_observador, precipitaciones.cod_modulo from municipio, precipitaciones, observador where municipio.cod_municipio=precipitaciones.cod_municipio
					  and municipio.cod_municipio='$cod_municipio' and precipitaciones.cod_observador=observador.cod_observador and date_part('year', inicio)='$gestion'
					  group by precipitaciones.comunidad, precipitaciones.cx, precipitaciones.cy, date_part('year', precipitaciones.inicio), observador.nombre, observador.apellido,
					  observador.cod_observador, precipitaciones.cod_modulo";
			}
  if($cod_modulo==3)
  			{
			  $SQL = "select periodo_hmd.comunidad, periodo_hmd.cx, periodo_hmd.cy, date_part('year', periodo_hmd.inicio) as anio, observador.nombre, observador.apellido,
					  observador.cod_observador, periodo_hmd.cod_modulo from municipio, periodo_hmd, observador where municipio.cod_municipio=periodo_hmd.cod_municipio
					  and municipio.cod_municipio='$cod_municipio' and periodo_hmd.cod_observador=observador.cod_observador and date_part('year', inicio)='$gestion'
					  group by periodo_hmd.comunidad, periodo_hmd.cx, periodo_hmd.cy, date_part('year', periodo_hmd.inicio), observador.nombre, observador.apellido,
					  observador.cod_observador, periodo_hmd.cod_modulo";
			}						
  $resultado = pg_query($con, $SQL) or die("Error con la BD".pg_last_error());
  while($row = pg_fetch_assoc($resultado))
	   {
	    $this->r[]=$row;
	   }
	   return $this->r;
 }
} 
?>