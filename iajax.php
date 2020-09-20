<?php
//agregar un retardo de 2 segundos para poder apreciar los mensajes
sleep(2);
header('content-type: application/json; charset=utf-8');//header para json
include_once 'puntosDAO.php';
$ac=isset($_POST["tipo"])?$_POST["tipo"]:"x";//parametro para determinar la accion
switch($ac){
	case "grabar":
		$p=new puntosDao();
		$exito=$p->grabar($_POST["titulo"], $_POST["cx"], $_POST["cy"]);
		if($exito==0)
				  {
				   $r["estado"]="Ok";
				   $r["mensaje"]="Grabado Correctamente";
				  }
			  else
			      {
				   $r["estado"]="Error";
				   $r["mensaje"]="Error al Grabar";
				  }
			 break;
	case "listar":
		$p=new puntosDao();
		$resultados=$p->listar_todo($_POST["gestion"], $_POST["cod_municipio"], $_POST["cod_modulo"]);
		if(sizeof($resultados)>0)
					{
					 $r["estado"]="Ok";
				     $r["mensaje"]=$resultados;
					}
				else
					{
					 $r["estado"]="Error";
				     $r["mensaje"]="No hay Registros";
					}
				break;
				default:
					 $r["estado"]="Error";
				     $r["mensaje"]="Datos No Validos";
				break;					 
}
echo json_encode($r);//imprimir json
?>