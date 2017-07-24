<?php
	public function databaseGetAction() {						
		$tables = DB::select("SHOW TABLE STATUS");		
		$table_num = $table_rows = $data_size = 0;
		$tabledb = array();
		foreach($tables as $table){
			$data_size = $data_size + $table['Data_length'];
			$table_rows = $table_rows + $table['Rows'];
			$table['Data_length'] = $this->sizecount($table['Data_length']);
			$table_num++;
			$tabledb[] = $table;
		}
		$data_size = $this->sizecount($data_size);
		$filename = str_replace('\\', '/', Yaf_Registry::get('config')['application']['dbpath']) . '/' . basename($_SERVER['HTTP_HOST'] . '_DB_' . date('YmdHis') . '.sql');
		
		$this->_view->assign('table_num', 	   $table_num);
        $this->_view->assign('tabledb',        $tabledb);
		$this->_view->assign('data_size', 	   $data_size);
		$this->_view->assign('filename', 	   $filename);
    }
	
	public function dblistAction() {		
		/***获取所有备份文件BOF***/
		$backupdbdir=	Yaf_Registry::get('config')['application']['dbpath'];
		$filenames	=	scandir($backupdbdir);
		$dbs		=	array();
		$k			=	1;
		foreach($filenames	as	$v){			
			if(!is_dir($v)){ 
				$filesize=round(abs(filesize($backupdbdir.'/'.$v))/1024,2);
				$filetime=date('Y-m-d H:i', filectime($backupdbdir.'/'.$v));
				array_push($dbs, array('id'=>$k++, 'name'=>$v, 'size'=>$filesize, 'time'=>$filetime));
			}
		}
		/***获取所有备份文件EOF***/
		$this->_view->assign('dataset',		$dbs);
    }
	
	public function dbdeleteAction(){		
		do{
			$dbfile	= $this->get('id','');
			if(empty($dbfile)){	
				$result	= array(
							'code'	=>	'300',
							'msg'	=>	'请选择要删除的备份.',
						);
				break;
			}else{
				$path 	= str_replace('\\', '/', Yaf_Registry::get('config')['application']['dbpath']) . '/' . $dbfile;
				@unlink($path);
				$result		= array(
							'code'	=>	'200',
							'msg'	=>	'操作成功',
						);
			}
		}while(FALSE);
				
		die(json_encode($result));
	}
	
	public function exportdbAction(){			
		do{
			$result = DB::select("SHOW tables");
			if (!$result){
				$result	= array(
							'code'	=>	'300',
							'msg'		=>	'数据表查询失败.',
						);
				break;
			}			
			$datetime = date('YmdHis');
			$filename = str_replace('\\', '/', Yaf_Registry::get('config')['application']['dbpath']) . '/' . basename($_SERVER['HTTP_HOST'] . '_DB_' . $datetime . '.sql');
			$fp = @fopen($filename, 'w');
			if($fp){
				$mysqldata = '';
				foreach($result as $currow){						
					$this->sqldumptable(reset($currow), $fp);						
				}
				fclose($fp);
			}
			$result	= array(
							'code'	=>	'200',
							'msg'		=>	"备份成功。\r\n文件名: ".basename($_SERVER['HTTP_HOST'] . '_DB_' . $datetime . '.sql'),
						);		
		}while(FALSE);
		json($result);
	}
	
	public function downloaddbAction(){	
		do{			
			$result = DB::select("SHOW tables");
			if (!$result){
				throw new Exception('数据表查询失败.');
				break;
			}				
			$datetime = date('YmdHis');
			$filename = str_replace('\\', '/', Yaf_Registry::get('config')['application']['dbpath']) . '/' . basename($_SERVER['HTTP_HOST'] . '_DB_' . $datetime . '.sql');
			$fp = @fopen($filename, 'w');
			if($fp){
				$mysqldata = '';
				foreach($result as $currow){
					$this->sqldumptable(reset($currow), $fp);
				}
				fclose($fp);
			}
			
			$path = str_replace('\\', '/', Yaf_Registry::get('config')['application']['dbpath']). '/';
			$name = basename($_SERVER['HTTP_HOST'] . '_DB_' . $datetime . '.sql');			
			$file = fopen($path . $name, "r"); // 打开文件
			// 输入文件标签
			Header("Content-type: application/octet-stream");
			Header("Accept-Ranges: bytes");
			Header("Accept-Length: ".filesize($path . $name));
			Header("Content-Disposition: attachment; filename=" . $name);
			// 输出文件内容
			echo fread($file,filesize($path . $name));
			fclose($file);
			exit();
		}while(FALSE);
	}
	
	public function downloadfileAction(){	
			$name = $this->get('id','');
			if( empty($name) ){
				throw new Exception("文件名不能为空.");
			}
			$path = str_replace('\\', '/', Yaf_Registry::get('config')['application']['dbpath']). '/' . $name;
			echo $path;
			if( !file_exists($path) ){
				throw new Exception("文件{$name}不存在.");
			}
			$file = fopen($path, "r"); // 打开文件
			// 输入文件标签
			Header("Content-type: application/octet-stream");
			Header("Accept-Ranges: bytes");
			Header("Accept-Length: ".filesize($path));
			Header("Content-Disposition: attachment; filename=" . $name);
			// 输出文件内容
			echo fread($file,filesize($path));
			fclose($file);
			exit();
	}
	
	private function sqldumptable($table, $fp=0) {
		$tabledump  = "DROP TABLE IF EXISTS $table;\r\n";
		$tabledump .= "CREATE TABLE $table (\r\n";
	
		$firstfield=1;
		$fields = DB::select("SHOW FIELDS FROM $table");
		foreach($fields as $field) {
			if (!$firstfield) {
				$tabledump .= ",\r\n";
			} else {
				$firstfield=0;
			}
			$tabledump .= "   `{$field['Field']}` {$field['Type']}";
			if ($field["Default"]!==NULL) {
				$tabledump .= " DEFAULT '{$field['Default']}'";
			}
			if ($field['Null'] != "YES") {
				$tabledump .= " NOT NULL";
			}
			if ($field['Extra'] != "") {
				$tabledump .= " {$field['Extra']}";
			}
		}
	
		$keys = DB::select("SHOW KEYS FROM $table");
		foreach($keys as $key){
			$kname=$key['Key_name'];
			if ($kname != "PRIMARY" && $key['Non_unique'] == 0) {
				$kname="UNIQUE|$kname";
			}
			if(!isset($index[$kname]) || !is_array($index[$kname])) {
				$index[$kname] = array();
			}
			$index[$kname][] = $key['Column_name'];
		}
	
		while(list($kname, $columns) = @each($index)) {
			$tabledump .= ",\r\n";
			$colnames=implode($columns,",");
	
			if ($kname == "PRIMARY") {
				$tabledump .= "   PRIMARY KEY ($colnames)";
			} else {
				if (substr($kname,0,6) == "UNIQUE") {
					$kname=substr($kname,7);
				}
				$tabledump .= "   KEY $kname ($colnames)";
			}
		}
	
		$tabledump .= "\r\n);\r\n\r\n";
		if ($fp) {
			fwrite($fp,$tabledump);
		}
		
		$fields 	= DB::select("SHOW COLUMNS FROM $table");
		$numfields 	= sizeof($fields);
		$rows 		= DB::select("SELECT * FROM $table");
		foreach($rows as $row){
			$tabledump = "INSERT INTO $table VALUES(";
			
			$firstfield=1;
			foreach($fields as $field){
				if( $firstfield==0 ){
					$tabledump.=", ";
				}else{
					$firstfield=0;
				}				
				if (!isset($row[$field['Field']])) {
						$tabledump .= "NULL";
					} else {
						$tabledump .= "'".addslashes($row[$field['Field']])."'";
					}
			}
						
			$tabledump .= ");\r\n";
	
			if ($fp) {
				fwrite($fp,$tabledump);
			} 
		}
		
		if ($fp) {
			fwrite($fp,"\r\n");
		}
	}
	private function sizecount($size) {
		if($size > 1073741824) {
			$size = round($size / 1073741824 * 100) / 100 . ' G';
		} elseif($size > 1048576) {
			$size = round($size / 1048576 * 100) / 100 . ' M';
		} elseif($size > 1024) {
			$size = round($size / 1024 * 100) / 100 . ' K';
		} else {
			$size = $size . ' B';
		}
		return $size;
	}
		
	public function rolesaddAction(){		
		/***获取所有控制器BOF***/
		$controllerdir	=	Yaf_Registry::get('config')['application']['directory'].'/controllers';
		$filenames		=	scandir($controllerdir);
		$controllers	=	array();
		foreach($filenames	as	$v){			
			if(!is_dir($v)){ 
				$ctlname	=	substr($v, 0, -4);
				array_push($controllers, array('name'=>$ctlname, 'flag'=>0));
			}			
		}
		/***获取所有控制器EOF***/
		$this->_view->assign('controllers', $controllers);
    }
	
	public function rolesincreaseAction(){
		do{
			$rolename	=	$this->getPost('rolename', 		'');
			$controllers=	$this->getPost('controllers',   []);
			if( empty($rolename)||empty($controllers) ){
				$result	= array(
							'code'	=>	'300',
							'msg'		=>	'权限名或控制器列表不能为空',
						);
			}else{
				$rows	=	array(
						'rolename'		=>	$rolename,
						'controllers'	=>	implode(',', $controllers),
						'created_at'	=>	date("Y-m-d H:i:s"),
				);
				if( DB::table('roles')->insert($rows)){
						$result	= array(
								'code'	=>	'200',
								'msg'	=>	'操作成功',	
						);
				}else{
						$result	= array(
								'code'	=>	'300',
								'msg'	=>	'添加权限组失败,请多试几下',
						);
				}
			}
		}while(FALSE);
		
		die(json_encode($result));
    }
	public function roleseditAction(){
		$id		= $this->get('id' , NULL);
		if($id==NULL) return FALSE;
     	$dataset= DB::table('roles')->find(intval($id));
		$roles	= explode(',', $dataset['controllers']);
		
		/***获取所有控制器BOF***/
		$controllerdir	=	$this->config->application->directory.'/controllers';
		$filenames		=	scandir($controllerdir);
		$controllers	=	array();
		foreach($filenames	as	$v){			
			if(!is_dir($v)){ 
				$ctlname	=	substr($v, 0, -4);
				if( in_array(strtolower($ctlname), array_map('strtolower', $roles)) ){
					array_push($controllers, array('name'=>$ctlname, 'flag'=>1)); 
				}else{
					array_push($controllers, array('name'=>$ctlname, 'flag'=>0)); 
				}
			}			
		}
		/***获取所有控制器EOF***/
		$this->_view->assign('dataset', $dataset);		
		$this->_view->assign('controllers', $controllers);
    }
	
    public function rolesupdateAction(){
    	do{
			$id			=	$this->get('id', 		'');
			$rolename	=	$this->get('rolename', 	'');
			$controllers=	$this->get('controllers', []);
			if( empty($id)||empty($rolename)||empty($controllers) ){
				$result	= array(
							'code'		=>	'300',
							'msg'		=>	'ID或权限名或控制器列表不能为空',
				);
			}else{
				$controllers=	empty($controllers) ? '':implode(',', $controllers);
				$rows		=	array(	
									'rolename'		=>	$rolename,
									'controllers'	=>	$controllers,
								);
				if(DB::table('roles')->where('id','=',$id)->update($rows)!==FALSE){
						$result	= array(
								'code'	=>	'200',
								'msg'	=>	'操作成功',	
						);
				}else{
						$result	= array(
								'code'	=>	'300',
								'msg'	=>	'更新失败,请多试几下',	
						);
				}
			}
		}while(FALSE);
		
		die(json_encode($result));
    }
	
}