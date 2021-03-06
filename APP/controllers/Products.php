<?php
use Illuminate\Database\Capsule\Manager as DB;

class ProductsController extends CoreController{
	
	public function categoriesAction(){		    	
		$this->_view->assign('uniqid',	 uniqid());
    }	
	public function categoriesGetAction() {			
		$sort	=	$this->getPost('sort', 'sortorder');
		$order	=	$this->getPost('order', 'desc');
		$keywords	= $this->getPost('keywords', '');
		$query		= DB::table('categories');
		if($keywords!==''){
			$query	=	$query	->where('title','like',"%{$keywords}%");
		}else{
			$query	=	$query	->where('up','=','0');
		}
		$total		= $query->count();
		$rows 		= $query->orderBy($sort,$order)							
							->select('id','title',DB::raw('if(status=1,"激活","") as status'),'sortorder','created_at','updated_at')
							->get();
		foreach($rows	as	$k=>$v){
				$rows[$k]['children']	=	DB::table('categories')->where('up','=',$v['id'])
										->select('id','title',DB::raw('if(status=1,"激活","") as status'),'sortorder','created_at','updated_at')
										->orderBy($sort,$order)
										->get();
		}					
		json(['total'=>$total, 'rows'=>$rows]);		
    }
	public function categoriesaddAction(){
		$dataset  	= DB::table('categories')->where('up','=',0)->get();
		$this->_view->assign('dataset', $dataset);
    }	
	public function categoriesincreaseAction(){
		do{
			if( $this->method!='POST' ){
				$result	= array(
							'code'=>	'300',
							'msg'	=>	'操作失败',										
						);
				break;
			}
			$title		= $this->getRequest()->getPost('title', '');
			$up			= $this->getRequest()->getPost('up', 	0);		
			$status	= $this->getPost('status', 0);
			$sortorder	= $this->getPost('sortorder', 0);			
			if( empty($title) ){
				$result	= array(
							'code'	=>	'300',
							'msg'		=>	'配件名称不能为空',
						);
				break;
			}
			$rows	= array(
								'title'		=>	$title,
								'up'		=>	$up,
								'status'	=>	$status,
								'sortorder'	=>	$sortorder,
								'created_at'=>	date('Y-m-d H:i:s'),
					);
			if( DB::table('categories')->insert($rows) ){
				$result	= array(
							'code'	=>	'200',
							'msg'		=>	'操作成功',								
						);
			}else{
				$result	= array(
							'code'=>	'300',
							'msg'	=>	'数据插入失败',	
						);
			}
		}while(FALSE);
		
		die(json_encode($result));
    }	
	public	function categorieseditAction(){    
		$dataset  	= DB::table('categories')->where('up','=',0)->get();
		
		$id			= intval($this->get('id', NULL));
		if($id==NULL){	return false;	}		
     	$mymenu  	= DB::table('categories')->find($id);

		$this->_view->assign('dataset', $dataset);
		$this->_view->assign('mymenu',  $mymenu);
    }	
    public function categoriesupdateAction(){
		do{
			if( $this->method!='POST' ){
				$result	= array(
							'code'	=>	'300',
							'msg'		=>	'操作失败',										
						);
				break;
			}
			$id			= $this->getPost('id', '');
			$title		= $this->getPost('title', '');
			$up			= $this->getPost('up', 	0);		
			$status		= $this->getPost('status', 0);
			$sortorder	= $this->getPost('sortorder', 0);			
			if( empty($id)||empty($title) ){
				$result	= array(
							'code'	=>	'300',
							'msg'		=>	'配件id与标题不能为空',
						);
				break;
			}
			if( $id==$up ){
				$result	= array(
							'code'	=>	'300',
							'msg'		=>	'上级配件循环设置.',
						);
				break;
			}
			$rows	=	array(	
							'title'		=>	$title,
							'up'		=>	$up,
							'status'	=>	$status,
							'sortorder'	=>	$sortorder,
							'updated_at'=>	date('Y-m-d H:i:s'),
						);
			if( DB::table('categories')->where('id','=',$id)->update($rows)!==FALSE ){
				$result	= array(
							'code'		=>	'200',
							'msg'		=>	'操作成功',	
						);
			}else{
				$result	= array(
							'code'		=>	'300',
							'msg'		=>	'更新失败',	
						);
			}
		}while(FALSE);
    			
		die(json_encode($result));
    }		
    public function categoriesdeleteAction(){	
		do{
			if($this->method!='POST'){
				$result	= array(
							'code'		=>	'300',
							'msg'		=>	'操作失败',										
						);
				break;				
			}
			$id	= $this->get('id', '');
			if( empty($id) ){
				$result	= array(
							'code'		=>	'300',
							'msg'		=>	'参数为空',
						);
				break;
			}
			if(DB::table('categories')->delete($id)){
				$result		= array(
							'code'		=>	'200',
							'msg'		=>	'操作成功',
							);						
			}else{
				$result		= array(
							'code'		=>	'300',
							'msg'		=>	'删除失败',
							);
			}
		}while(FALSE);	
		
		die(json_encode($result));    	
    }
	
	public function productsAction(){
		$this->_view->assign('uniqid',	 uniqid());
		
		$rows	= DB::table('categories')->where('up','=',0)->orderBy('sortorder','desc')->get();
		if(!empty($rows)&&is_array($rows)){
		foreach($rows	as	$k=>$v){
				$rows[$k]['recordcount']=	DB::table('products')->where('categories_id','=',$v['id'])->count();	
				$rows[$k]['children']	=	DB::table('categories')->where('up','=',$v['id'])->orderBy('sortorder','desc')->get();
				if(!empty($rows[$k]['children'])&&is_array($rows[$k]['children'])){
				foreach($rows[$k]['children']	as	$k1=>$v1){
					$rows[$k]['children'][$k1]['recordcount']=	DB::table('products')->where('categories_id','=',$v1['id'])->count();
				}}
		}}		
		
		$this->_view->assign('categories',	 $rows);
    }
	public function productsGetAction() {
		$page   =	$this->getPost('page', 1);
		$limit  =	$this->getPost('rows', 10);
		$offset	=	($page-1)*$limit;			
		$sort	=	$this->getPost('sort',  'sortorder');
		$order	=	$this->getPost('order', 'desc');
		$keywords		= $this->getPost('keywords', '');
		$categories_id	= $this->getPost('categories_id', 0);

		$query	= DB::table('products');
		if($categories_id>0){
			$query	=	$query->where('products.categories_id','=',$categories_id);
		}
		if($keywords!==''){
			$query	=	$query->where(function ($query) use($keywords) {
										$query->where('products.title','like',"%{$keywords}%")
											  ->orWhere('products.keywords','like',"%{$keywords}%");
									});						
		}
		$total		= $query->count();
		$rows 		= $query->join('categories','products.categories_id','=','categories.id')
							->orderBy($sort,$order)
							->offset($offset)
							->limit($limit)
							->select('products.id','products.title','products.logo','categories.title as classname','products.sortorder','products.keywords',DB::raw('if(go_products.status=1,"激活","失效") as status'),'products.created_at','products.updated_at')
							->get();			
						
		json(['total'=>$total, 'rows'=>$rows]);		
    }
	public function productsaddAction(){
		$categories	= DB::table('categories')->where('up','=',0)->orderBy('sortorder','DESC')->get();
		if(!empty($categories)&&is_array($categories)){
		foreach($categories as $k=>$v){
			$categories[$k]['children']	=	DB::table('categories')->where('up','=',$v['id'])->orderBy('sortorder','DESC')->get();
		}}		
		$this->_view->assign('categories', 	$categories);
    }	
	public function productsincreaseAction(){
		do{
			if( $this->method!='POST' ){
				$result	= array(
							'code'	=>	'300',
							'msg'	=>	'操作失败',										
						);
				break;
			}
			$title			= $this->getPost('title', '');
			$categories_id	= $this->getPost('categories_id', '');
			$keywords		= $this->getPost('keywords', '');			
			$sortorder		= $this->getPost('sortorder', '');
			$status			= $this->getPost('status', 		0);			
			$recommend		= $this->getPost('recommend',	0);			
			$content		= $this->getPost('content', '');			
			if( empty($title) || empty($content) ){
				$result	= array(
							'code'	=>	'300',
							'msg'		=>	'标题和内容不能为空',
						);
				break;
			}
			$rows	=	array(				
							'categories_id'	=>	$categories_id,
							'title'			=>	$title,
							'keywords'		=>	$keywords,
							'sortorder'		=>	$sortorder,
							'content'		=>	$content,
							'status'		=>	$status,
							'recommend'		=>	$recommend,							
							'created_at'	=>	date('Y-m-d H:i:s'),
						);	
			$files	= $this->getFiles('upfile', NULL);				
			if( $files!=NULL && $files['size']>0 ){
				if( $image = $this->_uploadPictureToCDN('upfile') ){
					$rows['logo']	=	$image;
				}else{
					$result	= array(
						'code'		=>	'300',
						'msg'		=>	'图片上传失败.',
					);
					break;
				}
			}elseif( preg_match('#<img.*?src\=[\"\']([^\"\']*)[\"\']#is', stripslashes($content), $imagesurl) ){
				$rows['logo']	=	$imagesurl[1];				
			}			
			if( DB::table('products')->insert($rows) ){				
				$result	= array(
							'code'		=>	'200',
							'msg'		=>	'操作成功',	
				);
			}else{
				$result	= array(
							'code'	=>	'300',
							'msg'	=>	'数据插入失败',	
				);
			}
		}while(FALSE);
		
		die(json_encode($result));
    }	
	public function productseditAction(){    
		$id			= $this->get('id', 0);
		$dataset  	= (new productsModel)->find(intval($id))->toArray();
		$this->_view->assign('dataset', $dataset);
			
		$categories	= DB::table('categories')->where('up','=',0)->orderBy('sortorder','DESC')->get();		
		if(!empty($categories)&&is_array($categories)){
		foreach($categories as $k=>$v){
			$categories[$k]['children']	=	DB::table('categories')->where('up','=',$v['id'])->orderBy('sortorder','DESC')->get();
		}}		
		$this->_view->assign('categories', 	$categories);
    }	
    public function productsupdateAction(){
		do{
			if( $this->method!='POST' ){
				$result	= array(
							'code'	=>	'300',
							'msg'	=>	'操作失败',										
						);
				break;
			}
			$id				= $this->getPost('id', '');
			$title			= $this->getPost('title', '');
			$categories_id	= $this->getPost('categories_id', '');
			$keywords		= $this->getPost('keywords', '');
			$sortorder		= $this->getPost('sortorder', '');
			$status			= $this->getPost('status', 		0);			
			$recommend		= $this->getPost('recommend',	0);			
			$content		= $this->getPost('content', '');			
			if( empty($title) || empty($content) ){
				$result	= array(
							'code'	=>	'300',
							'msg'		=>	'标题和内容不能为空',
						);
				break;
			}
			$rows	=	array(
							'categories_id'	=>	$categories_id,
							'title'			=>	$title,
							'keywords'		=>	$keywords,							
							'sortorder'		=>	$sortorder,
							'status'		=>	$status,
							'recommend'		=>	$recommend,							
							'content'		=>	$content,
							'updated_at'	=>	date('Y-m-d H:i:s'),
						);	
			$files	= $this->getFiles('upfile', NULL);				
			if( $files!=NULL && $files['size']>0 ){
				if( $image = $this->_uploadPictureToCDN('upfile') ){
					$rows['logo']	=	$image;
				}else{
					$result	= array(
						'code'		=>	'300',
						'msg'		=>	'图片上传失败.',
					);
					break;
				}
			}elseif( empty($rows['logo']) &&  preg_match('#<img.*?src\=[\"\']([^\"\']*)[\"\']#is', stripslashes($content), $imagesurl) ){
				$rows['logo']	=	$imagesurl[1];				
			}
			if( DB::table('products')->where('id','=',$id)->update($rows)!==FALSE ){				
				$result	= array(
							'code'		=>	'200',
							'msg'		=>	'操作成功',	
						);
			}else{
				$result	= array(
							'code'	=>	'300',
							'msg'	=>	'数据插入失败',	
						);
			}			
		}while(FALSE);
    			
		die(json_encode($result));
    }

    public function productsdeleteAction(){	
		do{
			if($this->method!='POST'){
				$result	= array(
							'code'		=>	'300',
							'msg'		=>	'操作失败',										
						);
				break;				
			}
			$id	= $this->get('id', '');
			if( empty($id) ){
				$result	= array(
							'code'		=>	'300',
							'msg'		=>	'参数为空',
						);
				break;
			}
			if(DB::table('productscontent')->delete($id) && DB::table('products')->delete($id)){
				$result		= array(
							'code'		=>	'200',
							'msg'		=>	'操作成功',
							);						
			}else{
				$result		= array(
							'code'		=>	'300',
							'msg'		=>	'删除失败',
							);
			}
		}while(FALSE);	
		
		die(json_encode($result));    	
    }
	
	
	
	
	
	
	
	
	
	public function carbrandAction(){
    	$this->_view->assign('uniqid',	 uniqid());
    }
	public function carbrandGetAction() {
		$page   =	$this->getPost('page', 1);
		$limit  =	$this->getPost('rows', 10);
		$offset	=	($page-1)*$limit;			
		$sort	=	$this->getPost('sort',  'letter');
		$order	=	$this->getPost('order', 'asc');
		$keywords	= trim($this->getPost('keywords', ''));		
		$query		= DB::table('carbrand');
		if($keywords!==''){
			$query	=	$query	->where('brand','like',"%{$keywords}%");
		}		
		$total		= $query->count();
		$rows 		= $query->orderBy($sort,$order)
							->offset($offset)
							->limit($limit)
							->select('id','brand','icon','letter',DB::raw('if(recommend=1,"推荐","") as recommend'),'sortorder','created_at','updated_at')
							->get();
		json(['total'=>$total, 'rows'=>$rows]);
    }
	public function carbrandaddAction(){
		return true;
    }
	public function carbrandincreaseAction(){
		do{
			if( $this->method!='POST' ){
				$result	= array(
							'code'=>	'300',
							'msg'	=>	'操作失败',										
						);
				break;
			}
			$file		= $this->getPost('images',[])[0];
			$title		= $this->getPost('title', '');
			$links		= $this->getPost('links', '');
			$sortorder	= $this->getPost('sortorder', 0);			
			$recommend		= $this->getPost('recommend', 	0);
			if( empty($title) ){
				$result	= array(
							'code'	=>	'300',
							'msg'		=>	'图片标题不能为空',
						);
				break;
			}
			$rows	= array(
								'file'		=>	$file,
								'title'		=>	$title,
								'links'		=>	$links,
								'sortorder'	=>	$sortorder,
								'recommend'	=>	$recommend,
								'created_at'=>	date('Y-m-d H:i:s'),
			);
			if( DB::table('carbrand')->insert($rows) ){
				$result	= array(
							'code'		=>	'200',
							'msg'		=>	'操作成功',	
						);
			}else{
				$result	= array(
							'code'=>	'300',
							'msg'	=>	'数据插入失败',	
						);
			}
		}while(FALSE);
		
		die(json_encode($result));
    }	
	public	function carbrandeditAction(){
		$id			= $this->get('id', NULL);
		if($id==NULL){	return false;	}		
     	$dataset  	= DB::table('carbrand')->find(intval($id));

		$this->_view->assign('dataset', $dataset);
    }
    public function carbrandupdateAction(){
		do{
			if( $this->method!='POST' ){
				$result	= array(
							'code'	=>	'300',
							'msg'	=>	'操作失败',										
						);
				break;
			}
			$id			= $this->getPost('id', '');
			$file		= $this->getPost('images',[])[0];
			$brand		= $this->getPost('brand', '');
			$letter		= $this->getPost('letter', '');			
			$sortorder	= $this->getPost('sortorder', 0);			
			$recommend	= $this->getPost('recommend', 	0);
			if( empty($id)||empty($brand) ){
				$result	= array(
							'code'	=>	'300',
							'msg'		=>	'ID与名称不能为空',
						);
				break;
			}
			$rows	=	array(	
							'icon'		=>	$file,
							'brand'		=>	$brand,
							'letter'	=>	$letter,
							'sortorder'	=>	$sortorder,
							'recommend'	=>	$recommend,
							'updated_at'=>	date('Y-m-d H:i:s'),
						);						
			if( DB::table('carbrand')->where('id','=',$id)->update($rows)!==FALSE ){
				$result	= array(
							'code'		=>	'200',
							'msg'		=>	'操作成功',	
						);
			}else{
				$result	= array(
							'code'		=>	'300',
							'msg'		=>	'更新失败',	
						);
			}
		}while(FALSE);
    			
		die(json_encode($result));
    }		
    public function carbranddeleteAction(){	
		do{
			if($this->method!='POST'){
				$result	= array(
							'code'=>	'300',
							'msg'	=>	'操作失败',										
						);
				break;				
			}
			$id	= $this->get('id', '');
			if( empty($id) ){
				$result	= array(
							'code'	=>	'300',
							'msg'		=>	'参数为空',
						);
				break;
			}
			if(DB::table('carbrand')->delete($id)){
				$result		= array(
							'code'	=>	'200',
							'msg'		=>	'操作成功',
							);						
			}else{
				$result		= array(
							'code'	=>	'300',
							'msg'		=>	'删除失败',
							);
			}
		}while(FALSE);	
		
		die(json_encode($result));    	
    }
	
	public function carfactoryAction(){
    	$this->_view->assign('uniqid',	 uniqid());
    }
	public function carfactoryGetAction() {
		$page   =	$this->getPost('page', 1);
		$limit  =	$this->getPost('rows', 10);
		$offset	=	($page-1)*$limit;			
		$sort	=	$this->getPost('sort',  'letter');
		$order	=	$this->getPost('order', 'asc');
		$keywords	= trim($this->getPost('keywords', ''));		
		$query		= DB::table('carfactory')->join('carbrand','carfactory.brand_id','=','carbrand.id');
		if($keywords!==''){
			$query	=	$query	->where('factory','like',"%{$keywords}%");
		}		
		$total		= $query->count();
		$rows 		= $query->orderBy($sort,$order)
							->offset($offset)
							->limit($limit)
							->select('carfactory.id','factory','brand','letter','carfactory.sortorder','carfactory.created_at','carfactory.updated_at')
							->get();
		json(['total'=>$total, 'rows'=>$rows]);
    }
	public function carfactoryaddAction(){
		$carbrand  	= DB::table('carbrand') ->select('id','letter','brand')
											->orderBy('letter', 'asc')
											->get();
		$this->_view->assign('carbrand', $carbrand);
    }
	public function carfactoryincreaseAction(){
		do{
			if( $this->method!='POST' ){
				$result	= array(
							'code'=>	'300',
							'msg'	=>	'操作失败',										
						);
				break;
			}
			$factory	= $this->getPost('factory', '');
			$brand_id	= $this->getPost('brand_id', '');
			$sortorder	= $this->getPost('sortorder', 0);			
			if( empty($factory) ){
				$result	= array(
							'code'	=>	'300',
							'msg'		=>	'名称不能为空',
						);
				break;
			}if( empty($brand_id) ){
				$result	= array(
							'code'	=>	'300',
							'msg'		=>	'品牌不能为空',
						);
				break;
			}
			$rows	= array(
								'brand_id'	=>	$brand_id,
								'factory'	=>	$factory,
								'sortorder'	=>	$sortorder,
								'created_at'=>	date('Y-m-d H:i:s'),
			);
			if( DB::table('carfactory')->insert($rows) ){
				$result	= array(
							'code'		=>	'200',
							'msg'		=>	'操作成功',	
						);
			}else{
				$result	= array(
							'code'=>	'300',
							'msg'	=>	'数据插入失败',	
						);
			}
		}while(FALSE);
		
		die(json_encode($result));
    }	
	public	function carfactoryeditAction(){
		$id			= $this->get('id', NULL);
		if($id==NULL){	return false;	}		
     	$dataset  	= DB::table('carfactory')->find(intval($id));
		$this->_view->assign('dataset', $dataset);
		
		$carbrand  	= DB::table('carbrand') ->select('id','letter','brand')
											->orderBy('letter', 'asc')
											->get();
		$this->_view->assign('carbrand', $carbrand);
    }
    public function carfactoryupdateAction(){
		do{
			if( $this->method!='POST' ){
				$result	= array(
							'code'	=>	'300',
							'msg'		=>	'操作失败',										
						);
				break;
			}
			$id			= $this->getPost('id', '');
			$factory	= $this->getPost('factory', '');
			$brand_id	= $this->getPost('brand_id', '');
			$sortorder	= $this->getPost('sortorder', 0);			
			$status		= $this->getPost('status', 	0);
			if( empty($id)||empty($factory) ){
				$result	= array(
							'code'	=>	'300',
							'msg'		=>	'ID与标题不能为空',
						);
				break;
			}
			$rows	=	array(	
							'brand_id'	=>	$brand_id,
							'factory'	=>	$factory,
							'sortorder'	=>	$sortorder,
							'updated_at'=>	date('Y-m-d H:i:s'),
						);						
			if( DB::table('carfactory')->where('id','=',$id)->update($rows)!==FALSE ){
				$result	= array(
							'code'		=>	'200',
							'msg'		=>	'操作成功',	
						);
			}else{
				$result	= array(
							'code'		=>	'300',
							'msg'		=>	'更新失败',	
						);
			}
		}while(FALSE);
    			
		die(json_encode($result));
    }		
    public function carfactorydeleteAction(){	
		do{
			if($this->method!='POST'){
				$result	= array(
							'code'=>	'300',
							'msg'	=>	'操作失败',										
						);
				break;				
			}
			$id	= $this->get('id', '');
			if( empty($id) ){
				$result	= array(
							'code'	=>	'300',
							'msg'		=>	'参数为空',
						);
				break;
			}
			if(DB::table('carfactory')->delete($id)){
				$result		= array(
							'code'	=>	'200',
							'msg'		=>	'操作成功',
							);						
			}else{
				$result		= array(
							'code'	=>	'300',
							'msg'		=>	'删除失败',
							);
			}
		}while(FALSE);	
		
		die(json_encode($result));    	
    }
	
	public function carseriesAction(){
    	$this->_view->assign('uniqid',	 uniqid());
    }
	public function carseriesGetAction() {
		$page   =	$this->getPost('page', 1);
		$limit  =	$this->getPost('rows', 10);
		$offset	=	($page-1)*$limit;			
		$sort	=	$this->getPost('sort',  'sortorder');
		$order	=	$this->getPost('order', 'asc');
		$keywords	= trim($this->getPost('keywords', ''));		
		$query		= DB::table('carseries')->join('carfactory','carseries.factory_id','=','carfactory.id');
		if($keywords!==''){
			$query	=	$query	->where('series','like',"%{$keywords}%");
		}		
		$total		= $query->count();
		$rows 		= $query->orderBy($sort,$order)
							->offset($offset)
							->limit($limit)
							->select('carseries.id','series','factory','carseries.sortorder','carseries.created_at','carseries.updated_at')
							->get();
		json(['total'=>$total, 'rows'=>$rows]);
    }
	public function carseriesaddAction(){
		$carbrand  	= DB::table('carbrand') ->select('id','letter','brand')
											->orderBy('letter', 'asc')
											->get();
		$this->_view->assign('carbrand', $carbrand);
    }
	public function carseriesincreaseAction(){
		do{
			if( $this->method!='POST' ){
				$result	= array(
							'code'=>	'300',
							'msg'	=>	'操作失败',										
						);
				break;
			}
			$series		= $this->getPost('series', '');
			$factory_id	= $this->getPost('factory_id', '');
			$sortorder	= $this->getPost('sortorder', 0);		
			if( empty($series) ){
				$result	= array(
							'code'	=>	'300',
							'msg'		=>	'名称不能为空',
						);
				break;
			}if( empty($factory_id) ){
				$result	= array(
							'code'	=>	'300',
							'msg'		=>	'厂家不能为空',
						);
				break;
			}
			$rows	= array(
								'factory_id'=>	$factory_id,
								'series'	=>	$series,
								'sortorder'	=>	$sortorder,
								'created_at'=>	date('Y-m-d H:i:s'),
			);
			if( DB::table('carseries')->insert($rows) ){
				$result	= array(
							'code'		=>	'200',
							'msg'		=>	'操作成功',	
						);
			}else{
				$result	= array(
							'code'=>	'300',
							'msg'	=>	'数据插入失败',	
						);
			}
		}while(FALSE);
		
		die(json_encode($result));
    }	
	public	function carserieseditAction(){
		$id			= $this->get('id', NULL);
		if($id==NULL){	return false;	}		
     	$dataset  	= DB::table('carseries')->find(intval($id));
		$this->_view->assign('dataset', $dataset);
		
		$factory_id	= $dataset['factory_id'];
		$carbrand_id= DB::table('carfactory')->find($factory_id)['brand_id'];
		$this->_view->assign('factory_id',  $factory_id);
		$this->_view->assign('carbrand_id', $carbrand_id);
		
		$carbrand  	= DB::table('carbrand') ->select('id','letter','brand')
											->orderBy('letter', 'asc')
											->get();
		$this->_view->assign('carbrand', $carbrand);
		
		$carfactory	= DB::table('carfactory')->select('id','factory')
											 ->where('brand_id', '=', $carbrand_id)
											 ->get();
		$this->_view->assign('carfactory', $carfactory);
    }
    public function carseriesupdateAction(){
		do{
			if( $this->method!='POST' ){
				$result	= array(
							'code'	=>	'300',
							'msg'		=>	'操作失败',										
						);
				break;
			}
			$id			= $this->getPost('id', '');
			$series		= $this->getPost('series', '');
			$factory_id	= $this->getPost('factory_id', '');
			$sortorder	= $this->getPost('sortorder', 0);		
			if( empty($id)||empty($series) ){
				$result	= array(
							'code'	=>	'300',
							'msg'		=>	'ID与标题不能为空',
						);
				break;
			}
			$rows	=	array(	
							'factory_id'=>	$factory_id,
							'series'	=>	$series,
							'sortorder'	=>	$sortorder,
							'updated_at'=>	date('Y-m-d H:i:s'),
						);						
			if( DB::table('carseries')->where('id','=',$id)->update($rows)!==FALSE ){
				$result	= array(
							'code'		=>	'200',
							'msg'		=>	'操作成功',	
						);
			}else{
				$result	= array(
							'code'		=>	'300',
							'msg'		=>	'更新失败',	
						);
			}
		}while(FALSE);
    			
		die(json_encode($result));
    }		
    public function carseriesdeleteAction(){	
		do{
			if($this->method!='POST'){
				$result	= array(
							'code'=>	'300',
							'msg'	=>	'操作失败',										
						);
				break;				
			}
			$id	= $this->get('id', '');
			if( empty($id) ){
				$result	= array(
							'code'	=>	'300',
							'msg'		=>	'参数为空',
						);
				break;
			}
			if(DB::table('carseries')->delete($id)){
				$result		= array(
							'code'	=>	'200',
							'msg'		=>	'操作成功',
							);						
			}else{
				$result		= array(
							'code'	=>	'300',
							'msg'		=>	'删除失败',
							);
			}
		}while(FALSE);	
		
		die(json_encode($result));    	
    }
	
	public function carmodelAction(){
    	$this->_view->assign('uniqid',	 uniqid());
    }
	public function carmodelGetAction() {
		$page   =	$this->getPost('page', 1);
		$limit  =	$this->getPost('rows', 10);
		$offset	=	($page-1)*$limit;			
		$sort	=	$this->getPost('sort',  'sortorder');
		$order	=	$this->getPost('order', 'asc');
		$keywords	= trim($this->getPost('keywords', ''));		
		$query		= DB::table('carmodel')->join('carseries','carmodel.series_id','=','carseries.id');
		if($keywords!==''){
			$query	=	$query	->where('model','like',"%{$keywords}%");
		}		
		$total		= $query->count();
		$rows 		= $query->orderBy($sort,$order)
							->offset($offset)
							->limit($limit)
							->select('carmodel.id','model','series','carmodel.sortorder','carmodel.created_at','carmodel.updated_at')
							->get();
		json(['total'=>$total, 'rows'=>$rows]);
    }
	public function carmodeladdAction(){
		$carbrand  	= DB::table('carbrand') ->select('id','letter','brand')
											->orderBy('letter', 'asc')
											->get();
		$this->_view->assign('carbrand', $carbrand);
    }
	public function carmodelincreaseAction(){
		do{
			if( $this->method!='POST' ){
				$result	= array(
							'code'=>	'300',
							'msg'	=>	'操作失败',										
						);
				break;
			}
			$model		= $this->getPost('model', '');
			$series_id	= $this->getPost('series_id', '');
			$sortorder	= $this->getPost('sortorder', 0);		
			if( empty($model) ){
				$result	= array(
							'code'	=>	'300',
							'msg'		=>	'名称不能为空',
						);
				break;
			}if( empty($series_id) ){
				$result	= array(
							'code'	=>	'300',
							'msg'		=>	'品牌不能为空',
						);
				break;
			}
			$rows	= array(
								'series_id'	=>	$series_id,
								'model'		=>	$model,
								'sortorder'	=>	$sortorder,
								'created_at'=>	date('Y-m-d H:i:s'),
			);
			if( DB::table('carmodel')->insert($rows) ){
				$result	= array(
							'code'		=>	'200',
							'msg'		=>	'操作成功',	
						);
			}else{
				$result	= array(
							'code'=>	'300',
							'msg'	=>	'数据插入失败',	
						);
			}
		}while(FALSE);
		
		die(json_encode($result));
    }	
	public	function carmodeleditAction(){
		$id			= $this->get('id', NULL);
		if($id==NULL){	return false;	}		
     	$dataset  	= DB::table('carmodel')->find(intval($id));
		$this->_view->assign('dataset', $dataset);
		
		$series_id	= $dataset['series_id'];		
		$factory_id	= DB::table('carseries')->find($series_id)['factory_id'];
		$carbrand_id= DB::table('carfactory')->find($factory_id)['brand_id'];
		$this->_view->assign('factory_id',  $factory_id);
		$this->_view->assign('carbrand_id', $carbrand_id);
		
		$carbrand  	= DB::table('carbrand') ->select('id','letter','brand')
											->orderBy('letter', 'asc')
											->get();
		$this->_view->assign('carbrand', $carbrand);
		
		$carfactory	= DB::table('carfactory')->select('id','factory')
											 ->where('brand_id', '=', $carbrand_id)
											 ->get();
		$this->_view->assign('carfactory', $carfactory);
    
		$carseries	= DB::table('carseries')->select('id','series')
											 ->where('factory_id', '=', $factory_id)
											 ->get();
		$this->_view->assign('carseries', $carseries);
	}
    public function carmodelupdateAction(){
		do{
			if( $this->method!='POST' ){
				$result	= array(
							'code'	=>	'300',
							'msg'		=>	'操作失败',										
						);
				break;
			}
			$id			= $this->getPost('id', '');
			$model		= $this->getPost('model', '');
			$series_id	= $this->getPost('series_id', '');
			$sortorder	= $this->getPost('sortorder', 0);				
			if( empty($id)||empty($model) ){
				$result	= array(
							'code'	=>	'300',
							'msg'		=>	'ID与标题不能为空',
						);
				break;
			}
			$rows	=	array(	
							'series_id'	=>	$series_id,
							'model'		=>	$model,
							'sortorder'	=>	$sortorder,
							'updated_at'=>	date('Y-m-d H:i:s'),
						);						
			if( DB::table('carmodel')->where('id','=',$id)->update($rows)!==FALSE ){
				$result	= array(
							'code'		=>	'200',
							'msg'		=>	'操作成功',	
						);
			}else{
				$result	= array(
							'code'		=>	'300',
							'msg'		=>	'更新失败',	
						);
			}
		}while(FALSE);
    			
		die(json_encode($result));
    }		
    public function carmodeldeleteAction(){	
		do{
			if($this->method!='POST'){
				$result	= array(
							'code'=>	'300',
							'msg'	=>	'操作失败',										
						);
				break;				
			}
			$id	= $this->get('id', '');
			if( empty($id) ){
				$result	= array(
							'code'	=>	'300',
							'msg'		=>	'参数为空',
						);
				break;
			}
			if(DB::table('carmodel')->delete($id)){
				$result		= array(
							'code'	=>	'200',
							'msg'		=>	'操作成功',
							);						
			}else{
				$result		= array(
							'code'	=>	'300',
							'msg'		=>	'删除失败',
							);
			}
		}while(FALSE);	
		
		die(json_encode($result));    	
    }

	public function autopartsAction(){		    	
		$this->_view->assign('uniqid',	 uniqid());
    }	
	public function autopartsGetAction() {			
		$sort	=	$this->getPost('sort', 'sortorder');
		$order	=	$this->getPost('order', 'desc');
		$keywords	= $this->getPost('keywords', '');
		$query		= DB::table('autoparts');
		if($keywords!==''){
			$query	=	$query	->where('title','like',"%{$keywords}%")
								->orWhere('description','like',"%{$keywords}%");
		}else{
			$query	=	$query	->where('up','=','0');
		}
		$total		= $query->count();
		$rows 		= $query->orderBy($sort,$order)							
							->select('id','title','description',DB::raw('if(recommend=1,"推荐","") as recommend'),'sortorder','created_at','updated_at')
							->get();
		foreach($rows	as	$k=>$v){
				$rows[$k]['children']	=	DB::table('autoparts')->where('up','=',$v['id'])
										->select('id','title','description',DB::raw('if(recommend=1,"推荐","") as recommend'),'sortorder','created_at','updated_at')
										->orderBy($sort,$order)
										->get();
		}					
		json(['total'=>$total, 'rows'=>$rows]);		
    }
	public function autopartsaddAction(){
		$dataset  	= DB::table('autoparts')->where('up','=',0)->get();
		$this->_view->assign('dataset', $dataset);
    }	
	public function autopartsincreaseAction(){
		do{
			if( $this->method!='POST' ){
				$result	= array(
							'code'=>	'300',
							'msg'	=>	'操作失败',										
						);
				break;
			}
			$title		= $this->getPost('title', '');
			$description= $this->getPost('description', '');
			$up			= $this->getPost('up', 	0);
			$recommend	= $this->getPost('recommend', 0);
			$sortorder	= $this->getPost('sortorder', 0);			
			if( empty($title) ){
				$result	= array(
							'code'	=>	'300',
							'msg'		=>	'配件名称不能为空',
						);
				break;
			}
			$rows	= array(
								'title'		=>	$title,
								'description'=>	$description,
								'up'		=>	$up,
								'recommend'	=>	$recommend,
								'sortorder'	=>	$sortorder,
								'created_at'=>	date('Y-m-d H:i:s'),
					);
			if( DB::table('autoparts')->insert($rows) ){
				$result	= array(
							'code'	=>	'200',
							'msg'		=>	'操作成功',								
						);
			}else{
				$result	= array(
							'code'=>	'300',
							'msg'	=>	'数据插入失败',	
						);
			}
		}while(FALSE);
		
		die(json_encode($result));
    }	
	public	function autopartseditAction(){    
		$dataset  	= DB::table('autoparts')->where('up','=',0)->get();
		
		$id			= intval($this->get('id', NULL));
		if($id==NULL){	return false;	}		
     	$mymenu  	= DB::table('autoparts')->find($id);

		$this->_view->assign('dataset', $dataset);
		$this->_view->assign('mymenu',  $mymenu);
    }	
    public function autopartsupdateAction(){
		do{
			if( $this->method!='POST' ){
				$result	= array(
							'code'	=>	'300',
							'msg'		=>	'操作失败',										
						);
				break;
			}
			$id			= $this->getPost('id', '');
			$title		= $this->getPost('title', '');
			$description= $this->getPost('description', '');
			$up			= $this->getPost('up', 	0);
			$recommend	= $this->getPost('recommend', 0);
			$sortorder	= $this->getPost('sortorder', 0);			
			if( empty($id)||empty($title) ){
				$result	= array(
							'code'	=>	'300',
							'msg'		=>	'配件id与标题不能为空',
						);
				break;
			}
			if( $id==$up ){
				$result	= array(
							'code'	=>	'300',
							'msg'		=>	'上级配件循环设置.',
						);
				break;
			}
			$rows	=	array(	
							'title'		=>	$title,
							'description'=>	$description,
							'up'		=>	$up,
							'recommend'	=>	$recommend,
							'sortorder'	=>	$sortorder,
							'updated_at'=>	date('Y-m-d H:i:s'),
						);
			if( DB::table('autoparts')->where('id','=',$id)->update($rows)!==FALSE ){
				$result	= array(
							'code'		=>	'200',
							'msg'		=>	'操作成功',	
						);
			}else{
				$result	= array(
							'code'		=>	'300',
							'msg'		=>	'更新失败',	
						);
			}
		}while(FALSE);
    			
		die(json_encode($result));
    }		
    public function autopartsdeleteAction(){	
		do{
			if($this->method!='POST'){
				$result	= array(
							'code'		=>	'300',
							'msg'		=>	'操作失败',										
						);
				break;				
			}
			$id	= $this->get('id', '');
			if( empty($id) ){
				$result	= array(
							'code'		=>	'300',
							'msg'		=>	'参数为空',
						);
				break;
			}
			if(DB::table('autoparts')->delete($id)){
				$result		= array(
							'code'		=>	'200',
							'msg'		=>	'操作成功',
							);						
			}else{
				$result		= array(
							'code'		=>	'300',
							'msg'		=>	'删除失败',
							);
			}
		}while(FALSE);	
		
		die(json_encode($result));    	
    }
	
	
	
	public function _uploadPictureToCDN($upfile) {
        $files	= $this->getFiles($upfile);
		if( $files!=NULL && $files['size']>0 ){
			$uploader  = new FileUploader();
			$files     = $uploader->getFile($upfile);
            if(!$files){
				return FALSE;
			}
            if($files->getSize()==0){
				return FALSE;
            }
			$config	= Yaf_Registry::get('config');
            if (!$files->checkExts($config['application']['upfileExts'])){				
            	return FALSE;
            }
			if (!$files->checkSize($config['application']['upfileSize'])){
            	return FALSE;
            }
			$cdnfilename = 'Images-t' . time().rand(100,999) . '.' . $files->getExt();
			if( $image = $this->uploadToCDN($files->getTmpName(), $cdnfileName) ){
				return $image;
			}else{
				return FALSE;
			}
		}
		
		return FALSE;
    }
	
	/**
     * deal imgage upload
     */
    private function _uploadPicture($upfile) {
        do {
            $uploader  = new FileUploader();
            $files     = $uploader->getFile($upfile);
            if(!$files) break; 
            if($files->getSize()==0){
				//throw new Exception('file size is zero.');
				break;
            }
			$config	= Yaf_Registry::get('config');
            if (!$files->checkExts($config['application']['upfileExts'])){				
            	//throw new Exception('文件类型出错, 只允许'.$config['application']['upfileExts']);
                break;
            }
			if (!$files->checkSize($config['application']['upfileSize'])){
            	//throw new Exception('文件大小出错, 不允许超过.'.$config['application']['upfileSize'].'字节');
                break;
            }
			
			$filename = 'home-t' . time() . '.' . $files->getExt();
			$descdir  = $config['application']['uploadpath'] . '/Home/';
			if( !is_dir($descdir) ){ mkdir($descdir); }
			$realpath = $descdir . $filename;			
			if($files->move($realpath)){				
				return str_replace('./', '/', $realpath);
			}
        }while(false);
        
        return false;
    }

	/***PHP上传文件到七牛cdn***/
	public function uploadToCDN($filePath, $cdnfileName){					
			// 需要填写你的 Access Key 和 Secret Key
			$accessKey = $this->config['application']['cdn']['accessKey'];
			$secretKey = $this->config['application']['cdn']['secretKey'];

			// 构建鉴权对象
			$auth = new \Qiniu\Auth($accessKey, $secretKey);
			// 要上传的空间
			$bucket = $this->config['application']['cdn']['bucket'];
			
			// 生成上传 Token
			$token = $auth->uploadToken($bucket);

			// 上传到七牛后保存的文件名
			$key = $cdnfileName;

			// 初始化 UploadManager 对象并进行文件的上传
			$uploadMgr = new \Qiniu\Storage\UploadManager;

			// 调用 UploadManager 的 putFile 方法进行文件的上传
			list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
			if ($err !== null) {
				return false;
			} else {
				return $this->config['application']['cdn']['url'] . $ret['key'];
			}
	}

}
