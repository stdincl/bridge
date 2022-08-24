<?php 
namespace stdincl\bridge;

use stdincl\bridge\IO;
use stdincl\bridge\Parse;
use stdincl\bridge\SQL;

session_start();

/*
	IO.framework:Driver crea controladores CRUD para una base de datos compatible.
	La interfaz sigue la estructura de navegacion segun la configuracion.

	Para usar este controlador las tablas de la base de datos y 
	todos sus columnas deben confirarse de la siguiente forma:

	Implementacion de tablas 
	------------------------
		1.- Todas las tablas deben tener una columa llamada -id- autoicrement clave primaria
		2.- Todas las tablas deben implementar la configuracion en comentarios de tabla con formato json
			{
				"class":"table", table:El usuario puede crear registros | bean:El usuario solo puede mantener un registro en la tabla, Se crea automaticamente
				"name":"", Nombre de la tabla
				"description":"", Descripcion de contenido
				"singular":"", Nombre singular
				"plural":"", Nombre plural
				"icon":"bullseye", Nombre del icono en fontawesome sin "fa-"
				"paginate":true|false Si se indica false los registros de la tabla no se paginaran,
				"exportable":true|false Si se puede descargar o no
				"creable":true|false Si se pueden crear o no,
				"showInMenu":true|false Si aparece en el menu principal o no
			}
	Implementacion de columnas
	--------------------------
		1.- Todas las columnas deben indicar como minimo su tipo
		2.- La columna -id- debe ser de tipo -id-

			{
				"type":"text", 
					id:Columna primaria
					text:Entrada de texto, 
					password:Entrada de texto oculto, 
					number:Entrada de numero, 
					decimal:Entrada de numero, 
					textarea:Texto multilinea, 
					textrich:Texto con formato html,
					bool:Campo chequeable 1 o 0
					date:Fecha mysql
					map:Ubicacion (lat,lng) Utiliza google maps
					file:Archivo adjunto
					pick:Seleccionar un id de otra tabla (que esta columna debe indicar id de otra tabla)
					stack:Clave foranea de otra tabla (que otra tabla debe indicar el id de esta tabla)
				"target":"", 
					Si type:pick => Nombre de tabla para seleccionar 
					Si type:stack => Nombre de tabla a la que esta columa pertenece o por la que sera stackeada
					Si type:<otro> => inutilizada
				"main":true|false, Si es true se muestra como columna en registro de tablas resumen,
				"key":true|false, Si es true se usa como columna principal para navegacion,
				"group":"", Agrupa los campos del formulario por pestañas
				"name":"Categoria de la noticia", Nombre para campos en formularios
				"size":1-12 Numero de 1 a 12 que indica el tamaño de columna en formularios
			}

*/

class Driver extends Controller {
	public static $rpp = 20;

	public static function header(){
		?>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
		<script type="text/javascript" src="/node_modules/jquery/dist/jquery.min.js"></script>
		<link rel="stylesheet" type="text/css" href="/node_modules/iox.js/dist/iox.css" />
		<script type="text/javascript" src="/node_modules/iox.js/dist/iox.js"></script>
		<link rel="stylesheet" type="text/css" href="/node_modules/@chenfengyuan/datepicker/dist/datepicker.min.css" />
		<script type="text/javascript" src="/node_modules/@chenfengyuan/datepicker/dist/datepicker.min.js"></script>
		<script type="text/javascript" src="/node_modules/gmap3/dist/gmap3.min.js"></script>
		<script type="text/javascript" src="/node_modules/inputmask/dist/jquery.inputmask.min.js"></script>
		<script type="text/javascript" src="/node_modules/jquery-maskmoney/dist/jquery.maskMoney.min.js"></script>
		<script type="text/javascript" src="/node_modules/jquery.nicescroll/dist/jquery.nicescroll.min.js"></script>
		<script type="text/javascript" src="/node_modules/jquery.rut/jquery.rut.min.js"></script>
		<link rel="stylesheet" type="text/css" href="/node_modules/owl.carousel/dist/assets/owl.carousel.min.css" />
		<script type="text/javascript" src="/node_modules/owl.carousel/dist/owl.carousel.min.js"></script>
		<link rel="stylesheet" type="text/css" href="/node_modules/simplebar/dist/simplebar.min.css" />
		<script type="text/javascript" src="/node_modules/simplebar/dist/simplebar.min.js"></script>
		<script type="text/javascript" src="/node_modules/socket.io-client/dist/socket.io.min.js"></script>
		<script type="text/javascript" src="/node_modules/tablesorter/dist/js/jquery.tablesorter.min.js"></script>
		<link rel="stylesheet" type="text/css" href="/node_modules/trumbowyg/dist/ui/trumbowyg.min.css" />
		<script type="text/javascript" src="/node_modules/trumbowyg/dist/trumbowyg.min.js"></script>
		<link rel="stylesheet" type="text/css" href="/node_modules/animate.css/animate.min.css" />
		<link rel="stylesheet" type="text/css" href="/node_modules/protip/protip.min.css" />
		<script type="text/javascript" src="/node_modules/protip/protip.min.js"></script>
		<script type="text/javascript" src="/io/repository-1000.js"></script>
		<script type="text/javascript" src="/io/lang.js"></script>
		<?php
	}
	
	public static function _uploadFile($file,$index){ 
		Driver::checkAdminUser();
		$file_name = $file['name']; 
		Parse::blob($file); 
		Parse::int($index);
		while (file_exists(IO::media().'/file/'.$file_name)) {
			$file_name = rand(1,10).'.'.$file_name;
		}
		file_put_contents(IO::media().'/file/'.$file_name, $file); 
		return array(
			'filename'=>$file_name,
			'index'=>$index
		); 
	}

	public static function tableToField($table,$tableTargetName='',$tableTargetID=''){
		Driver::checkAdminUser();
		foreach($table['columns'] as $i=>$c){
			if($c['config']['type']=='stack'&&$c['config']['target']==$tableTargetName){
				$foreignColumn = $c['COLUMN_NAME'];

				$foreignObject = Driver::get($tableTargetName,$tableTargetID);
				$foreignTable = Driver::table($tableTargetName);
				foreach($foreignTable['columns'] as $i=>$column){
					if($column['config']['key']){
						$foreignTableKeyColumn = $column['COLUMN_NAME'];
					}
				}
			}
		}

		$label = $foreignObject[$foreignTableKeyColumn];
		$label = strlen($label)>15?substr($label, 0,15).'...':$label;
		$out = array(
			'table'=>$table['TABLE_NAME'],
			'text'=>($foreignObject[$foreignTableKeyColumn]==''?$table['config']['plural']:($table['config']['plural'].':'.$label))
		);
		if(isset($foreignColumn)){
			$out['foreigns'] = array($foreignColumn=>$tableTargetID);
			$out['parent'] = Driver::objectToField(Driver::get($tableTargetName,$tableTargetID),$foreignTable);
		}
		return $out;
	}

	public static function objectToField($object,$table){
		Driver::checkAdminUser();
		# Detenerminar nombre del enlace
		$name = '';
		if($table['config']['class']=='bean'){
			$name = $table['config']['singular'];
		}else{
			$keyColumn = '';
			foreach($table['columns'] as $i=>$column){
				if($column['config']['key']){
					$keyColumn = $column['COLUMN_NAME'];
				}
			}
			$label = $object[$keyColumn];
			$label = strlen($label)>15?substr($label, 0,15).'...':$label;
			$name = $object[$keyColumn]==''?($table['config']['singular']):($table['config']['singular'].':'.$label);
		}

		# La tabla tiene parent?
		$tableParentName = '';
		$stackColumnName = '';

		foreach($table['columns'] as $i=>$column){
			if($column['config']['type']=='stack'){
				$tableParentName = $column['config']['target'];
				$stackColumnName = $column['COLUMN_NAME'];
			}
		}

		if($tableParentName!=''){
			# $tableParentName con id = $object[$stackColumnName]
			$parent = Driver::tableToField($table,$tableParentName,$object[$stackColumnName]);

		}elseif($table['config']['class']=='stack'){
			$parent = Driver::tableToField($table);
		}

		$out = array(
			'table'=>$table['TABLE_NAME'],
			'id'=>$object['id'],
			'text'=>$name
		);
		if(isset($parent)){
			$out['parent'] = $parent;
		}
		return $out;
	}
	public static function tree($tableID,$id,$tableTargetName,$tableTargetID){
		try {
			$table = Driver::table($tableID);
			return Driver::objectToField(Driver::get($tableID,$id),$table);
		}catch(\Exception $e){}
		try {
			return Driver::tableToField($table,$tableTargetName,$tableTargetID);
		}catch(\Exception $e){}
 
		return $out;
	}

	public static function get($table,$id){  
		Driver::checkAdminUser();
		Parse::int($id);
		$object = SQL::queryOne('
			select 
				*
			from 
				'.$table.'
			where 
				id = '.$id.'
		');
		return $object;
	}
	public static function _delete(
		$tableID,
		$id
	){
		Driver::checkAdminUser();
		$table = Driver::table($tableID);
		Parse::int($id);
		SQL::query('
			delete from 
				'.$table['TABLE_NAME'].'
			where 
				id = '.$id.'
		');
	}
	public static function _download($tableID,$foreigns){
		Driver::checkAdminUser();
		$table = Driver::table($tableID);
		$result = Driver::find($tableID,0,0,$foreigns);
		$csv = '';
		foreach($table['columns'] as $i=>$column){
			if($column['config']['type']!='stack'){
				Parse::decode($column['config']['name']);
				$csv .= $column['config']['name'].';';
			}
		}
		$csv .= "\n";

		foreach($result['list'] as $i=>$item){

			foreach($table['columns'] as $i=>$column){

				# Clear rows
				Parse::decode($item[$column['COLUMN_NAME']]);
				$item[$column['COLUMN_NAME']] = str_replace("\n", "", $item[$column['COLUMN_NAME']]);
				$item[$column['COLUMN_NAME']] = str_replace("\r\n", "", $item[$column['COLUMN_NAME']]);
				$item[$column['COLUMN_NAME']] = str_replace("\r", "", $item[$column['COLUMN_NAME']]);

				if($column['config']['type']=='stack'){
					
				}elseif($column['config']['type']=='pick'){
					try {
						$targetObject = Driver::get($column['config']['target'],$item[$column['COLUMN_NAME']]);
						$targetTable = Driver::table($column['config']['target']); 
						$out = '';
						foreach ($targetTable['columns'] as $j => $targetTableColumn) {
							if($targetTableColumn['config']['key']){
								$out = $targetObject[$targetTableColumn['COLUMN_NAME']];
							}
						}

						Parse::decode($out);
						$out = str_replace("\n", "", $out);

						$csv .= $out.';';
					} catch (\Exception $e) { 
						$csv .= 'Sin definir';
					}
				}elseif($column['config']['type']=='bool'){
					$csv .= ($item[$column['COLUMN_NAME']]=='1'?'Si':'No').';';
				}else{
					$csv .= $item[$column['COLUMN_NAME']].';';
				}
			}

			$csv .= "\n";
		}
		$file_name = $tableID.'.export.csv'; 
		while (file_exists(IO::media().'/file/'.$file_name)) {
			$file_name = rand(1,10).'.'.$file_name;
		}
		file_put_contents(IO::media().'/file/'.$file_name, $csv);
		return $file_name;
	}
	public static function find($table,$rpp=0,$page=0,$foreigns=''){
		Driver::checkAdminUser();
		$foreigns = is_array($foreigns)?$foreigns:array();
		$queryCondition = '';
		foreach ($foreigns as $column_name => $foreignValue) {
			$queryCondition .= ' and '.$column_name.' = \''.$foreignValue.'\'';
		}
		Parse::int($rpp,$page);
		$rpp = $rpp<0?0:$rpp;
		if($rpp<=0){
			$page = 0;
			$pages = 0;
			$total = 0;
			$list = SQL::query('
				select 
					*
				from 
					'.$table.'
				where 
					true '.$queryCondition.'
				order by 
					id asc
			');
		}else{
			$total = SQL::queryOne('
				select 
					count(*) as total
				from 
					'.$table.'
				where 
					true '.$queryCondition.'
			','total'); 
			$pages = ceil($total/$rpp); 
			$pages = $pages<1?1:$pages;
			$page = $page<1?1:$page;
			$page = $page>$pages?$pages:$page;
			$list = SQL::query('
				select 
					*
				from 
					'.$table.'
				where 
					true '.$queryCondition.'
				order by 
					id desc
				limit 
					'.($rpp*($page>1?($page-1):0)).','.$rpp.'
			');
		}
		return array(
			'list'=>$list,
			'rpp'=>$rpp,
			'page'=>$page,
			'pages'=>$pages
		);
	}
	public static function isAdmin(){
		return is_array(IO::session('IO_DRIVER_SESSION'));
	}
	public static function getAdmin(){
		Driver::checkAdminUser();
		return IO::session('IO_DRIVER_SESSION');
	}
	public static function checkAdminUser(){
		if(!Driver::isAdmin()){
			IO::denied();
		}
	}
	public static function _login($u,$p){
		$settings = IO::settings();
		if($settings['driver']['session']==''){
			IO::denied();
		}else{
			Parse::string($u,$p);
			try {
				IO::session(
					'IO_DRIVER_SESSION',
					SQL::queryOne(
						str_replace(
							'<:pass:>', 
							$p, 
							str_replace(
								'<:user:>', 
								$u, 
								$settings['driver']['session']
							)
						)
					)
				);
			} catch (\Exception $e) {
				IO::exception('Acceso denegado');
			}
		}
	}
	public static function _logout(){
		IO::session('IO_DRIVER_SESSION','');
	}
	public static function _deleteFileValue($table,$column,$id){
		
		$tableObject = Driver::table($table);
		try {  
			SQL::query('
				update 
					'.$table.'
				set 
					'.$column.' = \'\'
				where 
					id = '.$id.'
			');
		}catch(\Exception $e){} 
	}
	public static function _save($io_table){  
		Driver::checkAdminUser();
		$table = Driver::table($io_table);
		Parse::int($_POST['id']);
		foreach ($table['columns'] as $i => $column) { 
			switch ($column['config']['type']) {
				case 'id':
					# No update
				break;
				case 'text':
					Parse::string($_POST[$column['COLUMN_NAME']]);
					SQL::query('
						update 
							'.$table['TABLE_NAME'].'
						set 
							'.$column['COLUMN_NAME'].' = \''.$_POST[$column['COLUMN_NAME']].'\'
						where 
							id = '.$_POST['id'].'
					');
				break;
				case 'password':
					Parse::string($_POST[$column['COLUMN_NAME']]);
					SQL::query('
						update 
							'.$table['TABLE_NAME'].'
						set 
							'.$column['COLUMN_NAME'].' = \''.$_POST[$column['COLUMN_NAME']].'\'
						where 
							id = '.$_POST['id'].'
					');
				break;
				case 'number':
					Parse::int($_POST[$column['COLUMN_NAME']]);
					SQL::query('
						update 
							'.$table['TABLE_NAME'].'
						set 
							'.$column['COLUMN_NAME'].' = \''.$_POST[$column['COLUMN_NAME']].'\'
						where 
							id = '.$_POST['id'].'
					');
				break;
				case 'file':
					try { 
						$file_name = $_FILES[$column['COLUMN_NAME']]['name']; 
						Parse::blob($_FILES[$column['COLUMN_NAME']]);

						while (file_exists(IO::media().'/file/'.$file_name)) {
							$file_name = rand(1,10).'.'.$file_name;
						}
						SQL::query('
							update 
								'.$table['TABLE_NAME'].'
							set 
								'.$column['COLUMN_NAME'].' = \''.$file_name.'\'
							where 
								id = '.$_POST['id'].'
						');
						file_put_contents(IO::media().'/file/'.$file_name, $_FILES[$column['COLUMN_NAME']]); 
					}catch(\Exception $e){} 
				break;
				case 'files':
					Parse::string($_POST[$column['COLUMN_NAME']]);
					SQL::query('
						update 
							'.$table['TABLE_NAME'].'
						set 
							'.$column['COLUMN_NAME'].' = \''.$_POST[$column['COLUMN_NAME']].'\'
						where 
							id = '.$_POST['id'].'
					');
				break;
				case 'pick':
					Parse::int($_POST[$column['COLUMN_NAME']]);
					SQL::query('
						update 
							'.$table['TABLE_NAME'].'
						set 
							'.$column['COLUMN_NAME'].' = \''.$_POST[$column['COLUMN_NAME']].'\'
						where 
							id = '.$_POST['id'].'
					');
				break;
				case 'bool':
					Parse::bool($_POST[$column['COLUMN_NAME']]); 
					SQL::query('
						update 
							'.$table['TABLE_NAME'].'
						set 
							'.$column['COLUMN_NAME'].' = \''.$_POST[$column['COLUMN_NAME']].'\'
						where 
							id = '.$_POST['id'].'
					');
				break;
				case 'date': 

					try {
						Parse::date($_POST[$column['COLUMN_NAME']]);
						$_POST[$column['COLUMN_NAME']] = '\''.$_POST[$column['COLUMN_NAME']].'\'';
					} catch (\Exception $e) {
						$_POST[$column['COLUMN_NAME']] = 'NULL';
					}

					SQL::query('
						update 
							'.$table['TABLE_NAME'].'
						set 
							'.$column['COLUMN_NAME'].' = '.$_POST[$column['COLUMN_NAME']].'
						where 
							id = '.$_POST['id'].'
					');
				break;
				case 'map': 
					SQL::query('
						update 
							'.$table['TABLE_NAME'].'
						set 
							'.$column['COLUMN_NAME'].' = \''.$_POST[$column['COLUMN_NAME']].'\'
						where 
							id = '.$_POST['id'].'
					');
				break;
				case 'textarea':
					Parse::string($_POST[$column['COLUMN_NAME']]);
					SQL::query('
						update 
							'.$table['TABLE_NAME'].'
						set 
							'.$column['COLUMN_NAME'].' = \''.$_POST[$column['COLUMN_NAME']].'\'
						where 
							id = '.$_POST['id'].'
					');
				break;
				case 'textrich':
					Parse::string($_POST[$column['COLUMN_NAME']]);
					SQL::query('
						update 
							'.$table['TABLE_NAME'].'
						set 
							'.$column['COLUMN_NAME'].' = \''.$_POST[$column['COLUMN_NAME']].'\'
						where 
							id = '.$_POST['id'].'
					');
				break;
			} 
		}
		IO::exception('Datos guardados');
	}
	public static function tableExists($tableName){
		Driver::checkAdminUser();
		Parse::string($tableName);
		$settings = IO::settings();
		try {
			SQL::queryOne('
				select 
					* 
				from 
					information_schema.tables
				where 
					table_schema = \''.$settings['mysql']['database'].'\' 
				    and 
				    table_name = \''.$tableName.'\' 
					and 
					table_comment <> \'\'
				limit 
					0,1
			');
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}
	public static function jsonParse($text){
		return json_decode(
			str_replace(
				'”', 
				'"',
				str_replace(
					"\n", 
					'', 
					str_replace(
						"\t", 
						'', 
						str_replace(
							"“", 
							'"', 
							$text
						)
					)
				)
			),
			1
		); 
	}
	public static function parseTableConfig($tableConfig){
		Driver::checkAdminUser();
		$config = Driver::jsonParse($tableConfig); 
		$config = is_array($config)?$config:array();
		$outConfig = array(
			'class'=>'table',
			'name'=>'',
			'description'=>'',
			'paginate'=>false,
			'singular'=>'',
			'plural'=>'',
			'icon'=>'bullseye',
			'exportable'=>false,
			'creable'=>true,
			'showInMenu'=>true
		);
		foreach ($config as $k=>$v) {
			$outConfig[$k] = $v;
		}
		return $outConfig;
	}
	public static function parseColumnConfig($columnConfig){
		Driver::checkAdminUser();
		$config = Driver::jsonParse($columnConfig); 
		$config = is_array($config)?$config:array();
		$outConfig = array(
			'type'=>'',
			'name'=>'',
			'target'=>'',
			'main'=>false,
			'key'=>false,
			'group'=>'Datos principales',
			'size'=>12
		);
		foreach ($config as $k=>$v) {
			$outConfig[$k] = $v;
		}
		return $outConfig;
	}
	public static function tableColumns($table){
		Driver::checkAdminUser();
		$settings = IO::settings();
		$columns = SQL::query('
			select 
				*
			from 
				INFORMATION_SCHEMA.COLUMNS
			where
				table_schema = \''.$settings['mysql']['database'].'\' 
				and 
				TABLE_NAME = \''.$table.'\'

		');
		foreach($columns as $i => $column) {
			$columns[$i]['config'] = Driver::parseColumnConfig($column['COLUMN_COMMENT']);
		}
		return $columns;
	}
	public static function mainTables(){ 
		Driver::checkAdminUser();
		$settings = IO::settings();
		$tablesOut = array();
		$tables = SQL::query('
			select 
				* 
			from 
				information_schema.tables
			where 
				table_schema = \''.$settings['mysql']['database'].'\' 
				and 
				table_comment <> \'\'
		'); 
		foreach ($tables as $i => $table) {
			$tableColumns = Driver::tableColumns($table['TABLE_NAME']);
			$tables[$i]['columns'] = $tableColumns;
			$tables[$i]['config'] = Driver::parseTableConfig($table['TABLE_COMMENT']);
			$hasStack = false;
			foreach ($tableColumns as $j => $tableColumn) {
				if($tableColumn['config']['type']=='stack'){
					$hasStack = true;
				}
			}
			if(!$hasStack){
				$tablesOut[] = $tables[$i];
			}
		}
		return $tablesOut;
	}
	public static function childTables($tableID){ 
		Driver::checkAdminUser();
		$settings = IO::settings();
		$tablesOut = array();
		$tables = SQL::query('
			select 
				* 
			from 
				information_schema.tables
			where 
				table_schema = \''.$settings['mysql']['database'].'\' 
				and 
				table_comment <> \'\'
		'); 
		foreach ($tables as $i => $table) { 
			$tables[$i]['columns'] = Driver::tableColumns($table['TABLE_NAME']);  
			$tables[$i]['stacks'] = array();
			$tables[$i]['config'] = Driver::parseTableConfig($table['TABLE_COMMENT']); 
			foreach ($tables[$i]['columns'] as $j=>$column){
				if($column['config']['type']=='stack'){
					if($column['config']['target']==$tableID){ 
						$tables[$i]['stacks'][] = $column;
					}
				}
			}
			if(isset($tables[$i]['stacks'][0])){
				$tablesOut[] = $tables[$i];
			}
		}
		return $tablesOut;
	}
	public static function getRecordID($tableID){
		Driver::checkAdminUser();
		try {
			return SQL::queryOne('
				select 
					*
				from 
					'.$tableID.'
			','id'); 
		} catch (\Exception $e) {
			$result = Driver::createEmptyObject($tableID);
			return $result['id'];
		}
	}
	public static function _createEmptyObject($tableID,$foreigns=null){
		Driver::checkAdminUser();
		$table = Driver::table($tableID);
		$foreigns = is_array($foreigns)?$foreigns:array();
		$query  = 'insert into '.$table['TABLE_NAME'].' (';
		$query .= 	'id';
		foreach ($foreigns as $column_name => $foreignValue) {
			$query .= ','.$column_name;
		}
		$query .= ')values(';
		$query .= 	'null';
		foreach ($foreigns as $column_name => $foreignValue) {
			$query .= ',\''.$foreignValue.'\'';
		}
		$query .= ')';

		SQL::query($query);
		return array(
			'table' => $tableID,
			'id' => SQL::lastID()
		);
	}
	public static $tables = array();

	public static function tableDefinition($tableID){ 
		Driver::checkAdminUser();
		$settings = IO::settings();
		return SQL::queryOne('
			select 
				* 
			from 
				information_schema.tables
			where 
				table_schema = \''.$settings['mysql']['database'].'\' 
			    and 
			    table_name = \''.$tableID.'\' 
				and 
				table_comment <> \'\'
			limit 
				0,1
		');
	}

	public static function tableGroups($tableColumns){
		Driver::checkAdminUser();
		$groups = array();
		foreach ($tableColumns as $i => $tableColumn){
			if($tableColumn['config']['type']!='id'){
				$groups[$tableColumn['config']['group']] = 1;
			}
		}
		return array_keys($groups);
	}

	public static function table($tableID){ 
		Driver::checkAdminUser();
		if(isset(Driver::$tables[$tableID])){
			return Driver::$tables[$tableID];
		}
		$table = Driver::tableDefinition($tableID);
		$table['columns'] = Driver::tableColumns($table['TABLE_NAME']);  
		$table['columnGroups'] = Driver::tableGroups($table['columns']);  
		$table['stacks'] = array();
		$table['config'] = Driver::parseTableConfig($table['TABLE_COMMENT']); 
		foreach ($table['columns'] as $j=>$column){
			if($column['config']['type']=='stack'){ 
				$table['stacks'][] = $column; 
			}
		}
		$table['config'] = Driver::parseTableConfig($table['TABLE_COMMENT']);
		$table['childs'] = Driver::childTables($tableID); 
		Driver::$tables[$tableID] = $table;
		return $table;
	}
	public static function browser(){
		if(isset($_GET['quit'])){
			Driver::logout();
			IO::location('./');
		}
		?>
		<?php try { $adminUser = Driver::getAdmin(); ?>
		<?php
			$mainTables = Driver::mainTables();
			try {
				$table = Driver::table($_GET['table']);
				if($table['config']['class']=='bean'){
					$object = Driver::get(
						$table['TABLE_NAME'],
						Driver::getRecordID($table['TABLE_NAME'])
					);
				}else{
					$object = Driver::get($table['TABLE_NAME'],$_GET['id']);
				}
			} catch (\Exception $e) {}
			$browsedTables = array();
		?><!DOCTYPE html>
		<html class="io-driver">
		<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
			<title>Administrador de Contenidos</title>
			<?php Driver::header(); ?>
			<script type="text/javascript">
			$(function(){

				var ioFileBox = function(file){

					var extension = file.toLowerCase().split('.').pop();
					var isImage = extension.indexOf('png')>=0||extension.indexOf('jpg')>=0||extension.indexOf('jpeg')>=0||extension.indexOf('gif')>=0;
					var icon = 'bullseye';
					switch(extension){
						case 'pdf':
							icon = 'file-pdf';
						break;
						case 'doc':
							icon = 'file-word';
						break;
						case 'docx':
							icon = 'file-word';
						break;
						case 'pages':
							icon = 'file';
						break;
						case 'mp3':
							icon = 'file-audio';
						break;
						case 'wav':
							icon = 'file-audio';
						break;
						case 'mp4':
							icon = 'file-video';
						break;
					}
					var input = $([
						'<div class="io-file-wrap">',
							(
								isImage?
								('<div class="io-file-box" style="background-image:url(\'/media/file/'+file+'\')"></div>'):
								('<label class="io-file-label"><b><i class="fa fa-'+icon+'"></i>'+file+'</b></label>')
							),
							'<a href="#" class="fa fa-times io-file-delete"></a>',
							'<div class="io-file-action">',
								'<a href="#" class="fa fa-chevron-left io-file-order-l"></a>',
								'<a href="#" class="fa fa-chevron-right io-file-order-r"></a>',
							'</div>',
						'</div>'
					].join(''));
					input.find('.io-file-delete').on('click',function(e){
						e.preventDefault(); 
						var removeLineIndex = $(this).parent('.io-file-wrap').index();
						var files = $(this).parents('.io-driver-file-uploader').siblings('textarea').val();
							files = files.split("\n");
						var fileList = [];
						$.each(files,function(i,f){
							f = f.trim();
							if(f!=''){
								fileList.push(f);
							}
						});
						fileList.splice(removeLineIndex,1);
						$(this).parents('.io-driver-file-uploader').siblings('textarea').val(fileList.join("\n"));
						$(this).parent('.io-file-wrap').remove(); 
					});
					input.find('.io-file-action a').on('click',function(e){
						e.preventDefault();
						var removeLineIndex = $(this).parents('.io-file-wrap').index();
						var files = $(this).parents('.io-driver-file-uploader').siblings('textarea').val();
							files = files.split("\n");
						var fileList = [];
						$.each(files,function(i,f){
							f = f.trim();
							if(f!=''){
								fileList.push(f);
							}
						});
						if(removeLineIndex>=0&&removeLineIndex<=(fileList.length-1)){
							fileList.swap(removeLineIndex+($(this).hasClass('io-file-order-l')?-1:+1),removeLineIndex);
						}
						$(this).parents('.io-driver-file-uploader').siblings('textarea').val(fileList.join("\n"));
						$(this).parents('.io-driver-file-uploader').trigger('update');
					});
					return input;
				} 
				window.uploadToProcess = 0;
				window.uploadProcessed = 0;
				$('.io-driver-file-uploader')
					.on('update',function(){
						var _self = $(this).find('.io-driver-file-uploader-list');
						var files = $(this).siblings('textarea').val();
							files = files.split("\n");
						var fileList = [];
						$.each(files,function(i,f){
							f = f.trim();
							if(f!=''){
								fileList.push(f);
							}
						});
						_self.find('*').remove();
						$.each(fileList,function(i,f){
							ioFileBox(f).appendTo(_self);
						});
					})
					.on('addFile',function(){
						var files = $(this).siblings('textarea').val();
							files = files.split("\n");
						var fileList = [];
						$.each(files,function(i,f){
							f = f.trim();
							if(f!=''){
								fileList.push(f);
							}
						});
					})
					.on('dragenter',function(e){ e.preventDefault(); })
					.on('dragleave',function(e){ 
						e.preventDefault();
						$(this).removeClass('io-driver-file-uploader-active');
					})
					.on('dragover',function(e){ 
						e.preventDefault();
						$(this).addClass('io-driver-file-uploader-active');
					})
					.on('drop',function(ev){ 
						var _self = $(this);
						_self.removeClass('io-driver-file-uploader-active');
					 	ev.preventDefault();
					 	ev.dataTransfer = ev.originalEvent.dataTransfer;
					 	var ioFileLoads = [];
					 	var ioIndex = 0;
					 	window.uploadToProcess += ev.dataTransfer.items.length; 
						for (var i = 0; i < ev.dataTransfer.items.length; i++) {
							if (ev.dataTransfer.items[i].kind === 'file') {
								var file = ev.dataTransfer.items[i].getAsFile();

								ioFileLoads[ioIndex] = ioFileBox('Cargando...').appendTo(_self.find('.io-driver-file-uploader-list'));
								$.connect('Driver::uploadFile',{file:file,index:ioIndex},function(result){
									_self.siblings('textarea').val(
										_self.siblings('textarea').val()+"\n"+result.filename
									);
									result.index = parseInt(result.index,10);
									ioFileLoads[result.index].replaceWith(ioFileBox(result.filename));
									window.uploadProcessed++;
									if(window.uploadProcessed==window.uploadToProcess){
										_self.trigger('update');
									}
								},function(){
									window.uploadProcessed++; 
									if(window.uploadProcessed==window.uploadToProcess){
										_self.trigger('update');
									}
								},function(){
									window.uploadProcessed++; 
									if(window.uploadProcessed==window.uploadToProcess){
										_self.trigger('update');
									}
								},false);

								ioIndex++;
							}
						} 
				 	}).trigger('update');


				$('.io-driver-pages select').on('update',function(){
					$(this).siblings('span').text($(this).find('option:selected').text());
				}).on('change',function(){
					$(this).trigger('update');
				}).trigger('update');

				$('.io-driver-content-form').connect('Driver::save',true,function(){
					alert('Datos guardados');
				});
				$('.io-driver-actions-save').on('click',function(e){
					e.preventDefault();
					$('.io-driver-content-form').trigger('submit');
				});
				$('.io-driver .driver-download').connect('Driver::download',function(csvFile){
					location.href = '/media/file/'+csvFile;
				});
				$('.io-driver .create-empty-object').connect('Driver::createEmptyObject',function(result){
					$.location('./?table='+result.table+'&id='+result.id);
				});
				$('.io-driver .io-delete-row').on('click',function(e){
					e.preventDefault();
					var _self = $(this);
					if(confirm('Eliminar?')){
						$.connect('Driver::delete',{
							'tableID':$(this).attr('data-table'),
							'id':$(this).attr('data-id')
						},function(){
							_self.parents('tr').remove();
						});
					}
				});
				$('.io-driver-form-group-tabs a').on('click',function(e){
					e.preventDefault();
					$(this).addClass('active').siblings().removeClass('active');
					$($('.io-driver-form-group-content').get($(this).index())).show().siblings().hide();
				});
				$('.io-driver-form-group-tabs a:first-child').trigger('click');
				$('.io-driver .delete-file-value').on('click',function(e){
					e.preventDefault();
					if(confirm('Eliminar?')){
						$.connect('Driver::deleteFileValue',{
							'table':$(this).attr('data-table'),
							'column':$(this).attr('data-column'),
							'id':$(this).attr('data-id')
						},$.reload);
					}
				});
				$.each($('.io-driver .io-map-input'),function(i,mv){ 
					var _input = $(mv).siblings('input');
					var location = $(mv).attr('data-location')?$(mv).attr('data-location'):'0,0';
						location = location.split(',');
						location = (isNaN(parseFloat(location[0]))?'0':parseFloat(location[0]))+','+(isNaN(parseFloat(location[1]))?'0':parseFloat(location[1]));
						location = location.split(',');
					var lat = parseFloat(location[0]);
					var lng = parseFloat(location[1]);
					var map = new google.maps.Map(mv, {
						center: {
							lat:lat, 
							lng:lng
						},
						zoom: 16,
						mapTypeControl: false
					});
					var marker = new google.maps.Marker({
			            map: map, 
			            draggable:true,
			            position: {
							lat:lat, 
							lng:lng
						}
			        });
			        google.maps.event.addListener(marker,'dragend',function(event){
			        	_input.val(this.position.lat()+','+this.position.lng());
				    });
				});
			});
			</script>
		</head>
		<body>	
			<?php if(isset($table)){ ?>
				<div class="io-driver-main">
					<div class="io-driver-bread">
						<div class="io-driver-bread-wrap">
							<a href="./">Inicio</a>
							<?php 
								$steps = array();
								$foreignColumn = array_keys($_GET['foreigns'])[0];
								$foreignValue = $_GET['foreigns'][$foreignColumn];
								foreach ($table['columns'] as $i => $c) {
									if($c['COLUMN_NAME']==$foreignColumn){
										$tableTargetName = $c['config']['target'];
										$tableTargetID = $_GET['foreigns'][$foreignColumn];
									}
								}
								$treeRoot = Driver::tree(
									$table['TABLE_NAME'],
									$object['id'],
									$tableTargetName,
									$tableTargetID
								);
								while(isset($treeRoot)){ 

									$steps[] = $treeRoot;
									$browsedTables[] = $treeRoot['table'];

									if(isset($treeRoot['parent'])){
										$treeRoot = $treeRoot['parent'];
									}else{
										unset($treeRoot);
									}
								} 
								$steps = array_reverse($steps);
								foreach($steps as $i=>$step){
									$url = './?table='.$step['table'];
									if(isset($step['id'])){ $url .= '&id='.$step['id']; }
									if(isset($step['foreigns'])){ 
										foreach ($step['foreigns'] as $fo_column => $fo_value) {
											$url .= '&foreigns['.$fo_column.']='.$fo_value;
										}
									}
									?><a href="<?php echo $url; ?>"><?php echo $step['text']; ?></a><?php 
								}
							?>
						</div>
					</div>
					<?php if(isset($object)){ ?>
						<div class="io-driver-content">
							<div class="io-driver-content-wrap">
								<div class="io-driver-content-title"><b class="fa fa-<?php echo $table['config']['class']=='bean'?$table['config']['icon']:'pen-square'; ?>"></b> <?php echo $table['config']['singular']; ?></div>
								<div class="io-driver-content-description"><?php echo $table['config']['description']; ?></div>
								<form class="io-driver-content-form">
									<input type="hidden" name="io_table" value="<?php echo $table['TABLE_NAME']; ?>" />
									<div class="io-driver-form-group">
										<div class="io-driver-form-group-tabs"><?php foreach ($table['columnGroups'] as $i => $columnGroup) { ?><a href="#"><?php echo $columnGroup; ?></a><?php } ?></div>
										<div class="io-driver-form-group-contents">
											<?php foreach ($table['columnGroups'] as $i => $columnGroup) { ?>
												<div class="io-driver-form-group-content">
													<?php foreach($table['columns'] as $i=>$column){ ?> 
														<?php if($column['config']['group']==$columnGroup||$column['config']['type']=='id'){ ?>
															<?php 
															switch ($column['config']['type']) {
																case 'id':
																	?> 
																	<input type="hidden" name="<?php echo $column['COLUMN_NAME']; ?>" value="<?php echo $object[$column['COLUMN_NAME']]; ?>" />
																	<?php
																break;
																case 'stack':
																	?> 
																	<input type="hidden" name="<?php echo $column['COLUMN_NAME']; ?>" value="<?php echo $object[$column['COLUMN_NAME']]; ?>" />
																	<?php
																break;
																case 'textarea':
																	?>
																	<div class="io-col<?php echo $column['config']['size']; ?>">
																		<div class="io-input">
																			<label><?php echo $column['config']['name']; ?></label>
																			<textarea name="<?php echo $column['COLUMN_NAME']; ?>"><?php Parse::decode($object[$column['COLUMN_NAME']]); echo $object[$column['COLUMN_NAME']]; ?></textarea>
																		</div>
																	</div>
																	<?php
																break;
																case 'textrich':
																	?>  
																	<div class="io-col<?php echo $column['config']['size']; ?>"> 
																		<div class="io-input">
																			<label><?php echo $column['config']['name']; ?></label> 
																			<textarea class="textrich" name="<?php echo $column['COLUMN_NAME']; ?>" placeholder="<?php echo $column['config']['name']; ?>"><?php Parse::decode($object[$column['COLUMN_NAME']]); echo $object[$column['COLUMN_NAME']]; ?></textarea>
																		</div> 
																	</div>
																	<?php
																break;
																case 'file':
																	?>  
																	<div class="io-col<?php echo $column['config']['size']; ?>">
																		<div class="io-input">
																			<label>
																				<?php echo $column['config']['name']; ?>
																				<?php if($object[$column['COLUMN_NAME']]!=''){ ?>
																				<a href="/media/file/<?php echo $object[$column['COLUMN_NAME']]; ?>" download="<?php echo $object[$column['COLUMN_NAME']]; ?>" class="fa fa-cloud-download-alt"></a>
																				<?php } ?>
																			</label>
																			<?php if($object[$column['COLUMN_NAME']]!=''){ ?>
																				<i class="fa fa-times delete-file-value active" data-column="<?php echo $column['COLUMN_NAME']; ?>" data-table="<?php echo $table['TABLE_NAME']; ?>" data-id="<?php echo $object['id']; ?>"></i>
																			<?php } ?>
																			<input type="file" name="<?php echo $column['COLUMN_NAME']; ?>" placeholder="<?php echo $object[$column['COLUMN_NAME']]; ?>" />
																			<label></label>
																		</div>
																	</div>
																	<?php
																break;
																case 'files':
																	?>  
																	<div class="io-col<?php echo $column['config']['size']; ?>">
																		<div class="io-input">
																			<label><?php echo $column['config']['name']; ?></label>
																			<textarea class="io-driver-file-uploader-textarea" name="<?php echo $column['COLUMN_NAME']; ?>" placeholder="<?php echo $column['config']['name']; ?>"><?php Parse::decode($object[$column['COLUMN_NAME']]); echo $object[$column['COLUMN_NAME']]; ?></textarea>
																			<div class="io-driver-file-uploader">
																				<div class="io-driver-file-uploader-list"></div>
																			</div>
																		</div>
																	</div>
																	<?php
																break;
																case 'date':
																	?>  
																	<div class="io-col<?php echo $column['config']['size']; ?>">
																		<div class="io-input">
																			<label><?php echo $column['config']['name']; ?></label>
																			<input type="date" name="<?php echo $column['COLUMN_NAME']; ?>" value="<?php echo $object[$column['COLUMN_NAME']]; ?>" />
																		</div>
																	</div>
																	<?php
																break;
																case 'map':
																	?>  
																	<div class="io-col<?php echo $column['config']['size']; ?>">
																		<div class="io-input">
																			<label><?php echo $column['config']['name']; ?></label>
																			<input type="hidden" name="<?php echo $column['COLUMN_NAME']; ?>" value="<?php echo $object[$column['COLUMN_NAME']]; ?>" />
																			<div class="io-map-input" data-location="<?php echo $object[$column['COLUMN_NAME']]; ?>"></div>
																		</div>
																	</div>
																	<?php
																break;
																case 'bool':
																	?>  
																	<div class="io-col<?php echo $column['config']['size']; ?>">
																		<div class="io-input">
																			<label><?php echo $column['config']['name']; ?></label>
																			<input <?php echo $object[$column['COLUMN_NAME']]=='1'?' checked="checked"':''; ?> type="checkbox" name="<?php echo $column['COLUMN_NAME']; ?>" id="chk<?php echo $column['COLUMN_NAME']; ?>" />
																			<label for="chk<?php echo $column['COLUMN_NAME']; ?>"><?php echo $column['config']['name']; ?></label>
																		</div>
																	</div>
																	<?php
																break;
																case 'pick':
																	?> 
																	<?php 
																		$options = Driver::find($column['config']['target']);
																		$optionTable = Driver::table($column['config']['target']);
																	?>
																	<div class="io-col<?php echo $column['config']['size']; ?>">
																		<div class="io-input">
																			<label><?php echo $column['config']['name']; ?></label> 
																			<select name="<?php echo $column['COLUMN_NAME']; ?>">
																				<?php foreach($options['list'] as $j=>$option){ ?>
																				<option <?php if($option['id']==$object[$column['COLUMN_NAME']]){ ?> selected="selected"<?php } ?> value="<?php echo $option['id']; ?>">
																					<?php $contentOption = ''; ?>
																					<?php foreach($optionTable['columns'] as $k=>$c){ ?>
																						<?php if($c['config']['key']){?>
																							<?php $contentOption = $option[$c['COLUMN_NAME']]; ?>
																						<?php } ?>
																					<?php } ?>
																					<?php echo $contentOption; ?>
																				</option>
																				<?php } ?>
																			</select> 
																			<label></label>
																		</div>
																	</div>
																	<?php
																break;
																case 'text':
																	?>
																	<div class="io-col<?php echo $column['config']['size']; ?>">
																		<div class="io-input">
																			<label><?php echo $column['config']['name']; ?></label>
																			<input type="text" name="<?php echo $column['COLUMN_NAME']; ?>" value="<?php echo $object[$column['COLUMN_NAME']]; ?>" />
																		</div>
																	</div>
																	<?php
																break;
																case 'password':
																	?>
																	<div class="io-col<?php echo $column['config']['size']; ?>">
																		<div class="io-input">
																			<label><?php echo $column['config']['name']; ?></label>
																			<input type="password" name="<?php echo $column['COLUMN_NAME']; ?>" value="<?php echo $object[$column['COLUMN_NAME']]; ?>" />
																		</div>
																	</div>
																	<?php
																break;
																case 'number':
																	?>
																	<div class="io-col<?php echo $column['config']['size']; ?>">
																		<div class="io-input">
																			<label><?php echo $column['config']['name']; ?></label>
																			<input type="number" name="<?php echo $column['COLUMN_NAME']; ?>" placeholder="<?php echo $column['config']['name']; ?>" value="<?php echo $object[$column['COLUMN_NAME']]; ?>" />
																		</div>
																	</div>
																	<?php
																break;
																default:
																	?>
																	<div class="io-col<?php echo $column['config']['size']; ?>">
																		<div class="io-input">
																			<label><?php echo $column['config']['name']; ?></label>
																			<input type="text" name="<?php echo $column['COLUMN_NAME']; ?>" value="<?php echo $object[$column['COLUMN_NAME']]; ?>" />
																		</div>
																	</div>
																	<?php
																break;
															}
															?>
														<?php } ?>
													<?php } ?>
												</div>
											<?php } ?>
										</div>
									</div>
								</form>
								<?php if(isset($table['childs'][0])){ ?>
								<br />
								<br />
								<div class="io-driver-content-subtitle">Contenidos de <?php echo $table['config']['singular']; ?></div>
								<table class="io-driver-table">
									<tbody>
										<?php foreach($table['childs'] as $i=>$childTable){ ?>
											<tr> 
												<td><?php echo $childTable['config']['plural']; ?></td>  
												<td><?php echo $childTable['config']['description']; ?></td>  
												<td width="90">
													<?php 
														$path = '';
														foreach($childTable['stacks'] as $i=>$stack){
															if($stack['config']['target']==$table['TABLE_NAME']){
																$path .= '&foreigns['.$stack['COLUMN_NAME'].']='.$object['id'];
															}
														} 
													?>
													<div class="io-button-group">
														<a href="./?table=<?php echo $childTable['TABLE_NAME'].$path; ?>"><b class="fa fa-edit"></b> Administrar</a>
													</div>
												</td>
											</tr>
										<?php } ?>
									</tbody>
								</table> 
								<?php } ?>
							</div>
						</div>
						<div class="io-driver-actions">
							<div class="io-driver-actions-buttons">
								<a href="#" class="io-driver-actions-save">Guardar</a>
							</div>
						</div>
					<?php }elseif(isset($table)){ ?>
						<div class="io-driver-content">
							<div class="io-driver-content-wrap">
								<div class="io-driver-content-title"><b class="fa fa-<?php echo $table['config']['icon']; ?>"></b> <?php echo $table['config']['plural']; ?></div>
								<div class="io-driver-content-description"><?php echo $table['config']['description']; ?></div>
								<table class="io-driver-table">
									<thead>
										<tr>
											<?php foreach($table['columns'] as $i=>$column){ ?>
												<?php if($column['config']['main']){ ?>
													<th><?php echo $column['config']['name']; ?></th>
												<?php } ?>
											<?php } ?>
											<?php if($table['config']['creable']){ ?>
												<th>Acciones</th>
											<?php } ?>
										</tr>
									</thead>
									<tbody>
										<?php $data = Driver::find($table['TABLE_NAME'],$table['config']['paginate']?Driver::$rpp:0,$_GET['p'],$_GET['foreigns']); ?> 
										<?php foreach ($data['list'] as $i => $item){ ?>
											<tr> 
												<?php foreach($table['columns'] as $i=>$column){ ?>
													<?php if($column['config']['main']){ ?>
														<td>
														<?php if($column['config']['type']=='pick'){ ?>
															<?php 
																try {
																	$targetObject = Driver::get($column['config']['target'],$item[$column['COLUMN_NAME']]);
																	$targetTable = Driver::table($column['config']['target']); 
																	$out = '';
																	foreach ($targetTable['columns'] as $j => $targetTableColumn) {
																		if($targetTableColumn['config']['key']){
																			$out = $targetObject[$targetTableColumn['COLUMN_NAME']];
																		}
																	}
																	echo $out;
																} catch (\Exception $e) { 
																	echo 'Sin definir';
																}
															?>
														<?php }else if($column['config']['type']=='file'){ ?>
															<?php if($item[$column['COLUMN_NAME']]!=''){ ?>
																<a target="blank" download="<?php echo $item[$column['COLUMN_NAME']]; ?>" href="/media/file/<?php echo $item[$column['COLUMN_NAME']]; ?>"><?php echo $item[$column['COLUMN_NAME']]; ?></a>
															<?php }else{ ?>
																Sin <?php echo $column['config']['name']; ?>
															<?php } ?> 
														<?php }else if($column['config']['type']=='bool'){ ?>
															<?php 
																Parse::decode($item[$column['COLUMN_NAME']]);
																echo $item[$column['COLUMN_NAME']]=='1'?'Si':($item[$column['COLUMN_NAME']]=='0'?'No':''); 
															?>
														<?php }else{ ?>
															<?php 
																Parse::decode($item[$column['COLUMN_NAME']]);
																echo $item[$column['COLUMN_NAME']]; 
															?>
														<?php } ?>
														</td>
													<?php } ?>
												<?php } ?>
												<?php if($table['config']['creable']){ ?>
													<td>
														<div class="io-driver-item-actions"> 
															<a href="./?table=<?php echo $table['TABLE_NAME']; ?>&id=<?php echo $item['id']; ?>" class="io-driver-item-action-update">Editar</a>
															<a href="#" class="io-driver-item-action-delete io-delete-row" data-table="<?php echo $table['TABLE_NAME']; ?>" data-id="<?php echo $item['id']; ?>">Eliminar</a>
														</div>
													</td>
												<?php } ?>
											</tr>
										<?php } ?>
									</tbody>
								</table>
							</div>
						</div>
						<div class="io-driver-actions">
							<div class="io-driver-actions-buttons">
								<?php if($table['config']['creable']){ ?>
								<form class="create-empty-object io-driver-actions-create" data-prev="<?php echo $table['TABLE_NAME']; ?>" style="float:right;">
									<input type="hidden" name="tableID" value="<?php echo $table['TABLE_NAME']; ?>" />
									<?php foreach($table['stacks'] as $i=>$stack){ ?> 
										<input type="hidden" name="foreigns[<?php echo $stack['COLUMN_NAME']; ?>]" value="<?php echo $_GET['foreigns'][$stack['COLUMN_NAME']]; ?>" />
									<?php } ?>
									<div class="io-input fill pill green">
										<i class="fal fa-plus"></i>
										<button type="submit">Crear <?php echo $table['config']['singular']; ?></button>
									</div>
								</form>
								<?php } ?>
								<?php if($table['config']['exportable']){ ?>
								<form class="driver-download io-driver-actions-download" style="float:right; margin-right:10px">
									<input type="hidden" name="tableID" value="<?php echo $table['TABLE_NAME']; ?>" />
									<?php foreach($table['stacks'] as $i=>$stack){ ?> 
										<input type="hidden" name="foreigns[<?php echo $stack['COLUMN_NAME']; ?>]" value="<?php echo $_GET['foreigns'][$stack['COLUMN_NAME']]; ?>" />
									<?php } ?>
									<div class="io-input fill pill">
										<i class="fa fa-download"></i>
										<button type="submit">Descargar</button>
									</div>
								</form>
								<?php } ?>
							</div>
							<?php if($table['config']['paginate']){ ?>
							<div class="io-driver-pages">
								<form>
									<input type="hidden" name="table" value="<?php echo $table['TABLE_NAME']; ?>" />
									<?php $_GET['foreigns'] = is_array($_GET['foreigns'])?$_GET['foreigns']:array(); ?>
									<?php foreach($_GET['foreigns'] as $column_name=>$foreignValue){ ?>
										<input type="hidden" name="foreigns[<?php echo $column_name; ?>]" value="<?php echo $foreignValue; ?>" />
									<?php } ?>
									<div class="io-input">
										<select name="p" onchange="(function(){ $(this).parents('form').trigger('submit'); $.loader(); }).bind(this)();">
											<?php for($p=1; $p<=$data['pages']; $p++){ ?>
											<option <?php if($data['page']==$p){ ?> selected="selected"<?php } ?> value="<?php echo $p; ?>">Página <?php echo $p; ?></option>
											<?php } ?>
										</select>
										<label></label>
									</div>
								</form>
							</div>
							<?php } ?>
						</div>
					<?php } ?>
				</div>
			<?php }else{ ?>
				<?php 
					$urlExternalReference = dirname($_SERVER['SCRIPT_FILENAME']).'/'.($_GET['load']).'.php';
					$settings = IO::settings();
					if(
						isset($settings['driver']['pages'][$_GET['load']])
						&& 
						file_exists($urlExternalReference)
					){ 
				?>
				<div class="io-driver-main">
					<div class="io-driver-bread">
						<div class="io-driver-bread-wrap">
							<a href="./">Inicio</a>
							<a href="./?load=<?php echo $_GET['load']; ?>"><?php echo $settings['driver']['pages'][$_GET['load']]['name']; ?></a>
						</div>
					</div>
					<div class="io-driver-content">
						<div class="io-driver-content-wrap">
							<div class="io-driver-content-title"><b class="fa fa-<?php echo $settings['driver']['pages'][$_GET['load']]['icon']; ?>"></b> <?php echo $settings['driver']['pages'][$_GET['load']]['name']; ?></div>
							<?php require $urlExternalReference; ?>
						</div>
					</div>
				</div>
				<?php }else{ ?>
					<div class="io-driver-main">
						<table class="io-driver-main-empty">
							<tbody>
								<tr>
									<td>
										Selecciona un contenido para administrar
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				<?php } ?>
			<?php } ?>
			<div class="io-driver-sidebar">
				<div class="io-driver-sidebar-wrap">
					<div class="io-driver-sidebar-user">
						<div class="io-driver-sidebar-user-picture" style="background-image:url('/media/file/<?php echo $adminUser[$settings['driver']['user']['picture']]; ?>');"></div>
						<strong><?php echo $adminUser[$settings['driver']['user']['name']]; ?></strong>
						<a href="./?quit">Cerrar sesión</a>
					</div>
					<div class="io-driver-sidebar-links">
						<label>Administrar páginas</label>
						<a href="/">
							<i class="fa fa-home"></i>
							Ir al sitio
						</a>
						<?php foreach($mainTables as $i=>$childTable){ ?>
							<?php if($childTable['config']['showInMenu']){ ?>
								<a href="./?table=<?php echo $childTable['TABLE_NAME']; ?>"<?php if(in_array($childTable['TABLE_NAME'], $browsedTables)){ ?> class="active"<?php } ?>>
									<i class="fa fa-<?php echo $childTable['config']['icon']; ?>"></i>
									<?php echo $childTable['config']['name']; ?>
								</a>
							<?php } ?>
						<?php } ?>
						<?php $extraURLS = is_array($settings['driver']['pages'])?$settings['driver']['pages']:array(); ?>
						<?php foreach($extraURLS as $extraURLPath => $extraURL){ ?>
							<a href="?load=<?php echo $extraURLPath; ?>" <?php if($extraURLPath==$_GET['load']){ ?> class="active"<?php } ?>>
								<i class="fa fa-<?php echo $extraURL['icon']; ?>"></i>
								<?php echo $extraURL['name']; ?>
							</a>
						<?php } ?>
					</div>
				</div>
			</div>
		</body>
		</html>
		<?php } catch (\Exception $e) { ?>
		<html class="io-driver">
		<head>
			<title>Administrador de Contenidos</title>
			<?php Driver::header(); ?>
		</head>
		<body class="io-login-view">
			<table>
				<tbody>
					<tr>
						<td>
							<div class="io-login-wrap">
								<form data-connect="Driver::login">
									<div class="io-col12">
										<div class="io-input">
											<label>Usuario</label>
											<input type="text" name="u" />
										</div>
									</div>
									<div class="io-col12">
										<div class="io-input">
											<label>Clave de acceso</label>
											<input type="password" name="p" />
										</div>
									</div>
									<div class="io-col12">
										<div class="io-input fill"> 
											<input type="submit" value="Entrar" />
										</div>
									</div>
								</form>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
		</body>
		</html>
		<?php }
	}
}
?>