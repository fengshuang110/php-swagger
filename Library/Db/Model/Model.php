<?php 
namespace Library\Db\Model;

use Library\Data\Object;
class Model extends ModelAbstract {
	
	public function __construct(){
		parent::__construct();
		$this->table = '`'.$this->table.'`';
		$this->sql_helper = $this->getSqlHelper();
	}
	
	public function load($arr){
		foreach ($arr as $key=>$value){
			if(empty($value)){
				return false;
			}
		}
		return ture;
	}
	
	public function setTable($table){
		$this->table = $table;
		return $this;
	}
	
	public function setPrimaryKey($key){
		$this->PrimaryKey = $key;
		return $this;
	}
	/**
	 * 外键关联
	 */
	public function foreign(){
		if(!empty($this->foreign)){
			foreach ($this->foreign as $foreign){
				$this->sql_helper->join(
						"`".$foreign['table']."`",
						"`".$foreign['table']."`",
						$foreign['field'],
						$this->table.".".$foreign['foreignkey']."="."`".$foreign['table']."`.".$foreign['key']);
	
			}
		}
	}
	
	
	public function exChange($data){
		$object = new Object();
		if(empty($data)){
			return false;
		}
		foreach ($data as $key=>$value){
			$this->$key = $value;
		}
		return $this;
	}
	
	public function getOne(){
		$this->setAttribute();
		$sql = $this->sql_helper->__toString();
		$result = $this->conn()->preparedSql($sql, $this->sql_helper->bind)->fetchOne();
		$this->clean();
		return $result;
	}
	public function leftJoin($join){
		if(empty($join['table'])){
			$conds['table'] = $this->table;
		}
		$this->leftjoin[] = $join;
		return $this;
	}
	
	public function join($join){
		if(empty($join['table'])){
			$conds['table'] = $this->table;
		}
		$this->join[] = $join;
		return $this;
	}
	
	public function rightJoin($join){
		if(empty($join['table'])){
			$conds['table'] = $this->table;
		}
		$this->rightJoin[] = $join;
		return $this;
	}
	
	public function count(){
		$this->sql_helper = $this->setAttribute();
		$sql = $this->sql_helper->__toString();
		$count_sql = str_replace($this->sql_helper->select_field, "count(*) as count", $sql);
		$count = $this->conn()->preparedSql($count_sql, $this->sql_helper->bind)->fetchOne();
		$this->clean();
		return $count['count'];
	}
	
	public function setFileds($fields){
		$this->fields = $fields;
	}
	
	public function setAttribute($params=array()){
		$this->bind = array();
		$this->fields = $this->fields();
		if(empty($this->fields)){
			$this->fields = empty($params['field']) ? array("*") :$params['field'];
		}
		$params['start'] =empty($params['start']) ? 0:$params['start'];
		$params['limit'] =empty($params['limit']) ? 10:$params['limit'];
		$this->sql_helper->from($this->table)
		->select($this->table,$this->fields)
		->limit($params['start'],$params['limit']);
		
		if(!empty($params['groupby'])){
			foreach ($params['groupby'] as $groupby){
				$this->sql_helper->groupBy($this->table, $groupby);
			}
		}
		
		
		if(!empty($this->orderby)){
			foreach ($this->orderby as $orderby){
				if(empty($orderby['field'])){
					continue;
				}
				$orderby["sort"] = empty($orderby["sort"]) ? "desc" : $orderby["sort"];
				$this->sql_helper->orderBy($this->table, $orderby['field'],$orderby["sort"]);
			}
		}
		if(!empty($this->defaultSortField)){
			$this->sql_helper->orderBy($this->table, $this->defaultSortField,'desc');
		}
		$this->foreign();
		if(!empty($this->where)){
			foreach($this->where as $where){
				if(empty($where['field'])){
					continue;
				}
				$this->sql_helper->where($where['table'], $where['field'],$where['op'],$where['value']);
			}
		}
		
		if(!empty($this->orWhere)){
			foreach($this->orWhere as $where){
				if(empty($where['field'])){
					continue;
				}
				$this->sql_helper->orWhere($where['table'], $where['field'],$where['op'],$where['value']);
			}
		}
		
		if(!empty($this->leftjoin)){
			foreach ($this->leftjoin as $join){
				$this->sql_helper->leftJoin($join['table'],$join['table'],$join['fields'],$join['on']);
			}
		}
		if(!empty($this->join)){
			foreach ($this->join as $join){
				$this->sql_helper->join($join['table'],$join['table'],$join['fields'],$join['on']);
			}
		}
		
		if(!empty($this->rightjoin)){
			foreach ($this->rightjoin as $join){
				$this->sql_helper->rightJoin($join['table'],$join['table'],$join['fields'],$join['on']);
			}
		}
		
		return $this->sql_helper;
		
	}
	
	public function clean(){
		$this->orderby = array();
		$this->where = array();
		$this->leftjoin = array();
		$this->rightjoin = array();
		$this->join = array();
		$this->orWhere = array();
		$this->sql_helper->bind = array();
	}
	
	/**
	 * @param unknown $params
	 * @param string $is_count
	 * @return unknown
	 * $params = array(
	 * 		"field"=>array(),
	 * 		"limit"=>10
	 * 		"start"=>0,
	 * 		orderby = array(array("field"=>"id","sort"=>"desc"))
	 * )
	 */
	public function select($params = array()){
		$this->sql_helper = $this->setAttribute($params);
		$params['start'] =empty($params['start']) ? 0:$params['start'];
		$params['limit'] =empty($params['limit']) ? 10:$params['limit'];
		$sql = $this->sql_helper->__toString();
		$count_bind = $this->sql_helper->bind;
		$count_bind[':limit_start'] = 0;
		$count_sql = str_replace($this->sql_helper->select_field, "count(*) as count", $sql);
		$count = $this->conn()->preparedSql($count_sql, $count_bind)->fetchOne();
		$result = $this->conn()->preparedSql($sql, $this->sql_helper->bind)->fetchAll();
		$totalCount = $count['count'];
		
		$this->clean();
		$data['items'] = $result;
		$data['_meta'] = array(
				"totalCount"=>$totalCount,
				"pageCount"=>ceil($totalCount/$params['limit']),
				"currentPage"=>$params['start']/$params['limit']+1,
				"perPage"=>$params['limit']
		);
		
		return $data;
		
	}
	
	private function _load(){
		
		foreach($this as $key=>$value){
			if(in_array($key,array("foreign","table","primaryKey","alias","sql_helper"))){
				continue;
			}
			$data[$key] = $value;
		}
		if(empty($data)){
			return false;
		}
		return $data;
	}
	
	public function save($data = array()){
		if(empty($data)){
			$data = $this->_load();
		}
		if(empty($data) || !is_array($data)){
			throw new \Exception("数据不能为空");
		}
		$this->bind = array();
		if(array_key_exists($this->primaryKey,$data)){
			$record = $this->get($data[$this->primaryKey]);
		}
		
		if(!empty($record)){
			//更新方法
			$this->sql_helper->from($this->table)
			->update($this->table, $data)
			->where($this->table, $this->primaryKey, "=",$data[$this->primaryKey]);
			$sql = $this->sql_helper->__toString();
			
			$result = $this->conn()->preparedSql($sql, $this->sql_helper->bind)->affectedCount();
			$this->clean();
			return $result;
		}else{
			//保存方法
			$this->sql_helper->clean();
			$this->sql_helper->insert($this->table, $this->table, $data);
			$sql = $this->sql_helper->__toString();
			$result = $this->conn()->preparedSql($sql, $this->sql_helper->bind)->lastInsertId();
			$this->clean();
			return $result;
		}
	}
	
	public function del($id){
		if(!empty($id)){
			$this->sql_helper
			->delete($this->table)
			->where($this->table, $this->primaryKey, "=",$id);
			$sql = $this->sql_helper->__toString();
			$result = $this->conn()->preparedSql($sql, $this->sql_helper->bind)->affectedCount();
			$this->clean();
			return $result;
		}
		return false;
	}
	/**
	 * array("field"=>"1","op"=>"=","value"=>1)
	 * @param unknown $conds
	 */
	public function where($conds){
		if(empty($conds['table'])){
			$conds['table'] = $this->table;
		}
		$this->where[] = $conds;
		return $this;
	}
	
	public function orWhere($conds){
		if(empty($conds['table'])){
			$conds['table'] = $this->table;
		}
		$this->orWhere[] = $conds;
		return $this;
	}
	
	/**
	 * 
	 * @param unknown $orderby
	 */
	public function orderby($orderby){
		
		if(empty($orderby)){
			return $this;
		}
		if($this->arrayLevel($orderby) == 1){
			if(empty($orderby['table'])){
				$orderby['table'] = $this->table;
			}
			$this->orderby[] = $orderby;
			
		}else{
			foreach ($orderby as $value){
				
				if(empty($value['table'])){
					$orderby['table'] = $this->table;
				}
				$this->orderby[] = $value;
				
			}
		}
		return $this;
		
	}
	
	
		
	/**
	 * 获取全部  不需要分页
	 * $params['where'] = array("field"=>"1","op"=>"=","value"=>1)
	 * @param unknown $params
	 */
	public function all(){
		$this->sql_helper
			->from($this->table)
			->select($this->table);
		
		if(!empty($this->where)){
			foreach($this->where as $where){
				$this->sql_helper->where($where['table'], $where['field'],$where['op'],$where['value']);
			}
		}
		$this->foreign();
		
		if(!empty($this->orderby)){
			foreach ($this->orderby as $orderby){
				$orderby["sort"] = empty($orderby["sort"]) ? "desc" : $orderby["sort"];
				$this->sql_helper->orderBy($this->table, $orderby['field'],$orderby["sort"]);
			}
		}

		$sql = $this->sql_helper->__toString();
		$result = $this->conn()->preparedSql($sql, $this->sql_helper->bind)->fetchAll();
		$this->clean();
		return $result;
	}
	
	
	/**
	 * 获取单挑记录
	 * @param unknown $id
	 * @return boolean
	 */
	public function get($id){
		if(!empty($id)){
			$this->sql_helper
			->from($this->table)
			->select($this->table)
			->where($this->table, $this->primaryKey, "=",$id);
			$this->foreign();			
			$sql = $this->sql_helper->__toString();
			$result = $this->conn()->preparedSql($sql, $this->sql_helper->bind)->fetchOne();
			$this->clean();
			return $result;
		}
		return false;
	}
	
}
?>