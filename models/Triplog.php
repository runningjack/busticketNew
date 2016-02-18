<?php
/**
 * Created by PhpStorm.
 * User: USER
 * Date: 1/22/2016
 * Time: 2:12 AM
 */

namespace models;


use system\library\Database\Model;

class Triplog extends Model
{
    protected static $db_fields = array("id","trip_id","driver_id","bus_id","route_id","schedule_id","app_id","trip_count","fullname","destination_from","destination_to","start_time",
        "trip_date","end_time","total_hrs","speedo_start","speedo_end","speedo_total","fuel_level_stop","fuel_level_start","oil_level","brake_fluid_level",
        "status","passenger","remark","service_id","view","created_at","updated_at");

    protected static $table="fleetlogs";

    public $id;
    public $trip_id;
    public $driver_id;
    public $bus_id;
    public $route_id;
    public $schedule_id;
    public $app_id;
    public $trip_count;
    public $fullname;
    public $destination_from;
    public $destination_to;
    public $trip_date;
    public $start_time;
    public $end_time;
    public $total_hrs;
    public $speedo_start;
    public $speedo_end;
    public $speedo_total;
    public $fuel_level_stop;
    public $fuel_level_start;
    public $oil_level;
    public $brake_fluid_level;
    public $status;
    public $passenger;
    public $remark;
    public $service_id;
    public $view;
    public $created_at;
    public $updated_at;



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
}