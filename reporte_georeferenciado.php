<style>
    .mapContainer, #map{
        position:relative;
        width:100%;
        height:400px;
    }

    .customNavBar {
        position:absolute;
        top: 10px;
        left: 10px;
    }
    #impresora{
      position: relative;
      right: -50px;
      width: 45px;
      height: 45px;
    }
.Estilo1 {color: #FF0000; font-weight: bold; font-size: 10px; font-family: Verdana, Arial, Helvetica, sans-serif; }    
.Estilo18 {font-size: 9px; color: #000000; font-family: Verdana, Arial, Helvetica, sans-serif; }
.Estilo19 {font-size: 9px; color: #FF0000; font-family: Verdana, Arial, Helvetica, sans-serif; }
</style>

<?php
  $i=0;  
  $inicio="";
  $fin="";  
  $nombre=0;
  $locations=array(); // creating array to store 
  $uname="root"; //Username of a database.
  $pass="cloud";  //Password of database.
  $servername="localhost"; //Database servername.
  $dbname="sistec";  // Your database name.
  $db=new mysqli($servername,$uname,$pass,$dbname); // This line is connecting to database using mysqli method.
  if(isset($_POST['submit'])){
        $inicio=$_POST['inicio'];
        $fin=$_POST['fin'];
        $nombre=$_POST['nombre'];        
        if($nombre!=0){                        
                        if($inicio!="" && $fin!=""){                       
                                  $query =  $db->query("SELECT u.name, s.latitud, s.longitud, s.observacion, s.fecha_registro FROM seguimiento s, users u WHERE s.id_usuario=u.id AND u.id='".$nombre."' AND DATE_FORMAT(s.fecha_registro,'%Y-%m-%d') BETWEEN '".$inicio."' AND '".$fin."' ORDER BY s.fecha_registro");
                                 }
                             else
                                 {
                                  $query =  $db->query("SELECT u.name, s.latitud, s.longitud, s.observacion, s.fecha_registro FROM seguimiento s, users u WHERE s.id_usuario=u.id AND u.id='".$nombre."' ORDER BY s.fecha_registro");
                                 }      
                       }
                   else
                       {
                        if($inicio!="" && $fin!=""){
                                  $query =  $db->query("SELECT u.name, s.latitud, s.longitud, s.observacion, s.fecha_registro FROM seguimiento s, users u WHERE s.id_usuario=u.id AND DATE_FORMAT(s.fecha_registro,'%Y-%m-%d') BETWEEN '".$inicio."' AND '".$fin."' ORDER BY s.fecha_registro");
                                 }
                             else
                                 {
                                  $query =  $db->query("SELECT u.name, s.latitud, s.longitud, s.observacion, s.fecha_registro FROM seguimiento s, users u WHERE s.id_usuario=u.id ORDER BY s.fecha_registro");
                                 }
                       }     
  }else{
    $query =  $db->query("SELECT u.name, s.latitud, s.longitud, s.observacion, s.fecha_registro FROM seguimiento s, users u WHERE s.id_usuario=u.id ORDER BY s.fecha_registro");
  }
  
  while( $row = $query->fetch_assoc() ){ //fetching row and column from location table.
      $name = $row['name']; 
      $latitud = $row['latitud'];
      $longitud = $row['longitud'];                              
      $observacion=utf8_encode($row['observacion']);      
      $fecha=substr($row['fecha_registro'], 8, 2)."-".substr($row['fecha_registro'], 5, 2)."-".substr($row['fecha_registro'], 0, 4);
      $hora=substr($row['fecha_registro'], 11);
      /* Each row is added as a new array */
      $locations[]=array( 'name'=>$name, 'latitud'=>$latitud, 'longitud'=>$longitud, 'observacion'=>$observacion, 'fecha'=>$fecha, 'hora'=>$hora );
  }  
  ?>
  <?php
$page_title = 'Reporte Georeferenciado';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
   page_require_level(1);   
   $all_users = find_all_campo('users', 'name');
   /*if(isset($_POST['submit'])){
      $i=1;      
      $inicio=$_POST['inicio'];
      $fin=$_POST['fin'];
      $nombre=$_POST['nombre'];      
      $seguimientos = find_seguimiento_by_date($nombre, $inicio, $fin);
  }else{    
    $i=0;
    $seguimientos = find_all_seguimiento();    
  } */       
?>
<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDrMTfBa9NXyO3izpTE1hrR96YGxmMin4g"></script>
<!--<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?key=AIzaSyBzGEjVew1csMm8nx2v7tZzUiwl2YjZZes"></script>-->
<script type="text/javascript">
    var map;
    var Markers = {};
    var infowindow;
    var locations = [
        <?php  for($i=0;$i<sizeof($locations);$i++){ $j=$i+1;?>
        [
            'AMC Service',
            '<TABLE BORDER="4" BORDERCOLOR="#006666" CELLSPACING="0">'+
                    '<TR>'+
                         '<td width="50%" height="50%" bgcolor="#CCCCCC">'+'<div align="center">'+'<span class="Estilo1">'+
                         'TECNICO'+'</span>'+'<br>'+
                         '<span class="Estilo18">'+'<?php echo $locations[$i]['name'];?>'+'</span>'+'<br>'+
                         '<span class="Estilo19">'+'Coord(X, Y).:'+'</span>'+'<span class="Estilo18">'+'<?php echo $locations[$i]['latitud'];?>, <?php echo $locations[$i]['longitud'];?>'+'</span>'+'<br>'+
                         '<span class="Estilo19">'+'Actividad.:'+'</span>'+'<span class="Estilo18">'+'<?php echo $locations[$i]['observacion'];?>'+'</span>'+'<br>'+
                         '<span class="Estilo19">'+'Fecha.:'+'</span>'+'<span class="Estilo18">'+'<?php echo $locations[$i]['fecha'];?><br>'+'</span>'+
                         '<span class="Estilo19">'+'Hora.:'+'</span>'+'<span class="Estilo18">'+'<?php echo $locations[$i]['hora'];?><br>'+'</span>'+
                    '</TR>'+
            '</TABLE>',
            //'<p>Aqui esta la Direccion<a href="<?php //echo $locations[$i]['observacion'];?>"></a></p>',
            <?php echo $locations[$i]['latitud'];?>,
            <?php echo $locations[$i]['longitud'];?>,
            //0
        ]<?php if($j!=sizeof($locations))echo ","; }?>
    ];
    var origin = new google.maps.LatLng(locations[0][2], locations[0][3]);
    function initialize() {
      var mapOptions = {
        zoom: 6,
        center: origin,
        mapTypeId: google.maps.MapTypeId.TERRAIN

      };
      map = new google.maps.Map(document.getElementById('map'), mapOptions);
        infowindow = new google.maps.InfoWindow();
        for(i=0; i<locations.length; i++) {
            var position = new google.maps.LatLng(locations[i][2], locations[i][3]);
            var marker = new google.maps.Marker({
                position: position,
                map: map,
                icon: 'iconos/IconoGoogle.png',
            });
            google.maps.event.addListener(marker, 'click', (function(marker, i) {
                return function() {
                    infowindow.setContent(locations[i][1]);
                    infowindow.setOptions({maxWidth: 200});
                    infowindow.open(map, marker);
                }
            }) (marker, i));
            Markers[locations[i][4]] = marker;
        }
        locate(0);
    }
    function locate(marker_id) {
        var myMarker = Markers[marker_id];
        var markerPosition = myMarker.getPosition();
        map.setCenter(markerPosition);
        google.maps.event.trigger(myMarker, 'click');
    }
    google.maps.event.addDomListener(window, 'load', initialize);        
</script>  

<?php include_once('layouts/header.php'); ?>
<body>
<div class="row">
  <div class="col-md-6">
    <?php echo display_msg($msg); ?>
  </div>
</div>
<div class="row">
  <div class="col-md-9">
    <div class="panel">
      <div class="panel-heading">

      </div>
      <div class="panel-body">
          <form id="form1" class="panel-heading" method="post" action="reporte_georeferenciado.php">
            
              <div class="form-group">
                <label class="form-label">Busqueda por Tecnico y Rango de Fechas</label>
                  <div class="input-group">
                    <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                    <select class="form-control" name="nombre">
                      <option value="">Sel.</option>
                      <?php foreach ($all_users as $use): 
                               if($use['user_level']==3){
                                    if($nombre==$use['id']){?>  
                                              <option value="<?php echo (int)$use['id'] ?>" selected><?php echo $use['name'] ?></option>
                                    <?php }else{?>
                                              <option value="<?php echo (int)$use['id'] ?>"><?php echo $use['name'] ?></option>
                                    <?php }?>          
                         <?php }?>  
                      <?php endforeach;?>
                    </select>
                    <!--<input type="text" class="form-control" name="nombre" placeholder="Nombre del Tecnico">-->
                    <span class="input-group-addon"><i class="glyphicon glyphicon-calendar" aria-hidden="true"></i></span>
                    <input type="text" class="datepicker form-control" value="<?php echo $inicio;?>" name="inicio" placeholder="Desde">
                    <span class="input-group-addon"><i class="glyphicon glyphicon-menu-right"></i></span>                  
                    <span class="input-group-addon"><i class="glyphicon glyphicon-calendar" aria-hidden="true"></i></span>
                    <input type="text" class="datepicker form-control" value="<?php echo $fin;?>" name="fin" placeholder="Hasta">                  
                  </div>
              </div>
              <div class="form-group">
                <div class="input-group">
                   <button type="submit" name="submit" class="btn btn-primary">Ejecutar Busqueda</button>                                                  
                   <?php echo "<a href='seguimiento_PDF.php?nombre=$nombre&inicio=$inicio&fin=$fin' title='Imprimir Seguimiento' target='_blank'><span id='impresora' class='input-group-addon'><i class='glyphicon glyphicon-print' aria-hidden='true'></i></span></a>";?>
                </div>    
              </div>
             
          </form>
          <div class="mapContainer">                        
            <div style="text-align: center; margin: 10px auto;">              
              <div id="map" style="position:relative; width:100%; height:600px; border: 2px solid red;"></div>                         
            </div>
          </div>
    </div>
  </div>

</div>
<?php include_once('layouts/footer.php'); ?>
</body>