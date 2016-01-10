<?php
/**
 * Created by PhpStorm.
 * User: Amedora
 * Date: 7/5/15
 * Time: 4:14 AM
 */

namespace models;
use system\library\Database\Model;
use system\library\Verify;

class Merchant extends Model {
    protected  static $db_fields=array('id','app_id','key_salt','c_fname','c_lname','phone','store_contact','account_no','bank_name','sort_code',
        'email','username','password','store','device_IMEI','address','city','state','verified','logged_in','image','created_at','updated_at');
    protected static $table ="merchants";

    public  $id;
    public $store_contact;
    public $app_id;
    public $key_salt;
    public $account_no;
    public $bank_name;
    public $sort_code;
    public  $c_fname;
    public  $c_lname;
    public  $phone;
    public  $email;
    public  $username;
    public  $password;
    public  $store;
    public  $device_IMEI;
    public  $address;
    public  $city;
    public  $state;
    public  $verified;
    public  $logged_in;
    public  $image;
    public  $created_at;
    public  $updated_at;

    protected   function attributes(){
        //return get_object_vars($this);
        $instance = new static;
        $attributes = array();
        foreach(self::$db_fields as $field){
            if(property_exists($this,$field)){
                $attributes[$field] =$this->$field;
            }
        }
        return $attributes;
    }
    public static function findByAppId($id){
        $instance = new static;
        $sql =  $instance->query("SELECT * FROM ".static::$table." where app_id = '".$id."'" );
        if($sql->execute()){
            //$result_set =$sql->fetch(PDO::FETCH_ASSOC) ;
            $object_array = array();
            while ($row = $sql->fetch(\PDO::FETCH_ASSOC)) {
                $object_array[] = static::instantiate($row);
            }
            return !empty($object_array) ? array_shift($object_array) : false;
        }
    }
    public function pinAction($id,$input){

        $verify = new Verify("","","",$input); //em\library\Verify("","","",$input);
        return $verify->post(array("number"=>$input['phone']),"merchants",$id);
    }
    public static function uniqueID(){
        return DB::table("merchants")->max("id");
    }

} 