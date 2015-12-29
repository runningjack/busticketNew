<?php
/**
 * Created by PhpStorm.
 * User: Amedora
 * Date: 12/10/15
 * Time: 10:38 PM
 */
?>
<?php
require 'vendor/autoload.php';
require 'config.php';

$app = new Slim\App();

$app->get('/', function ($request, $response, $args) {
    $rer  = \models\Bus::all();
    $response->write(json_encode($rer));
    return $response;
});
/**
 * Buses list
 */
$app->get('/buses/index',function($request,$response,$args){
    $rer  = \models\Bus::all();
    $response->write(json_encode($rer));
    return $response;
});

/**
 * List Tickets
 */

$app->get('/tickets/index',function($request,$response,$args){
    $rer  = \models\Tickets::all();
    $response->write(json_encode($rer));
    return $response;
});

/**
 * Update tickets
 */

$app->get('/tickets/update[/{id}/{status}]', function($request,$response,$args){
    $ticket  = \models\Tickets::find($args['id']);
    $ticket->status = $args['status'];
    $rer=[];

    if($ticket->update()){
        $rer['data']=$ticket;
        $rer['msg']="success";
    }else{
        $rer['data']=null;
        $rer['msg']="failed";
    }
    $response->write(json_encode($rer));
    return $response;
});
/**
 * Get Tickets by appid
 */
$app->get('/tickets/data[/{appid}]',function($request,$response,$args){
    $rer  = \models\Tickets::find_by_sql("SELECT * FROM tickets WHERE app_id ='".$args['appid']."'");
    $response->write(json_encode($rer));
    return $response;
})->setArgument('id', '1');
/**
 * Create Ticketing
 */
$app->post("/ticketing/create/",function($request,$response,$args){
    try{

        $json = $request->getBody();
        $data = json_decode($json, true);

        $myTicketing = new \models\Ticketing();
        //$ticketing = json_decode($args['ticketing']);
        foreach($data as $key=>$val){
            $myTicketing->$key = $val;
        }
        if($myTicketing->create()){
            $result['success']  =true;
            $result['data']     =$myTicketing;
            $result['msg']      ="Data Updated";
            $result['code']     ="200";

        }else{

            $result['data']     =null;
            $result['msg']      ="failed";
            $result['success']  =false;
            $result['code']     ="501";
        }
    }catch(Exception $e){
        $rer['msg'] = $e->getMessage();
        $result['data']     =null;
        $result['success']  =false;
        $result['code']     ="501";
    }

    $response->write(json_encode($result));
    return $response;
});
/**
 * Update ticketing
 */
$app->get('/ticketing/update[/{ticketid}/{status}]', function($request,$response,$args){
    $ticket  = \models\Ticketing::findUniqueByColumn("ticket_id",$args['ticketid']);
    $ticket->status = $args['status'];


    if($ticket->update()){
        $result['success']  =true;
        $result['data']     =$ticket;
        $result['msg']      ="Data Updated";
        $result['code']     ="200";
    }else{
        $result['data']     =null;
        $result['msg']      ="failed";
        $result['success']  =true;
        $result['code']     ="501";
    }
    $response->write(json_encode($result));
    return $response;
});
/**
 * Create Merchant
 */
$app->post("/merchants/create/",function($request,$response,$args){
    try{
        $json = $request->getBody();
        $data = json_decode($json, true);
        $merchant = new \models\Merchant();
        //$ticketing = json_decode($args['ticketing']);
        if(!empty($data)){
            foreach($data as $key=>$val){
                $merchant->$key = $val;
            }
        }
        $merchant->created_at =date("Y-m-d H:i:s");
        $input['number']=$data['phone'];
        $input['key_salt']="";
        $merchant->password = \system\library\Hashing\Shahash::make($data['password'],$input);

        $v =    new system\library\Validator\Validator( array(
            new system\library\Validator\Validate\Unique("email","is already existing","merchants"),
            new system\library\Validator\Validate\Required('email'," is required"),
            new system\library\Validator\Validate\Unique("phone","is already existing","merchants"),
            new system\library\Validator\Validate\Unique("app_id","is already existing","merchants"),
            new system\library\Validator\Validate\Required('phone'," is required")
        ),$data);
        if($v->execute() == true){

            if($merchant->create()){
                if(isset($input['phone'])){
                    $merchant->pinAction($merchant->id,$data);
                }
               // $result                 =   array();
                $result['success']      =   true;
                $result['msg']          =   "Record Created";
                $result['data']           =   $merchant->id;
                $result['code']         =   "200";

            }else{
                //$result                 =   array();
                $result['success']      =   false;
                $result['msg']       =   "Merchant could not be created";
                $result['code']         =   "501";
                //throw new \Exception("Customer could not be created"); //return "error"; //unsuccessful

            }
        }else{
            $v_result = $v->getErrors();

            $result['success']      =   false;
            $result['msg']       =   $v_result;
            $result['code']         =   "501";

        }


    }catch(Exception $e){
        $result['msg'] = $e->getMessage();
        $result['data']=null;
        $result['success']      =   false;
    }

    $response->write(json_encode($result));
    return $response;
});
/**
 * Create Account
 */
$app->post("/account/create/",function($request,$response,$args){
    try{

        $json = $request->getBody();
        $data = json_decode($json, true);
//$result = array();
        $account = new \models\Account();
        //$ticketing = json_decode($args['ticketing']);
       if(!empty($data)){
            foreach($data as $key=>$val){
                $account->$key = $val;
            }
        }

       $account->created_at =date("Y-m-d H:i:s");

        $v =    new system\library\Validator\Validator( array(
            new system\library\Validator\Validate\Required("route_id"," is required "),
            new system\library\Validator\Validate\Required('station_id'," is required"),
            new system\library\Validator\Validate\Unique("app_id","is already existing","accounts"),

        ),$data);
        if($v->execute() == true){

            if($account->create()){
                $result['success']      =   true;
                $result['msg']          =   "Account Created on server Successfully";
                $result['data']         =   $account->id;
                $result['code']         =   "200";
            }else{
                //$result               =   array();
                $result['success']      =   false;
                $result['msg']          =   " Unexpected Error! Account could not be created. Please try again ";
                $result['data']         = null;
                $result['code']         =   "501";
                //throw new \Exception("Customer could not be created"); //return "error"; //unsuccessful
            }
        }else{
            $v_result = $v->getErrors();

            $result['success']      =   false;
            $result['msg']          =   $v_result;
            $result['data']         =   null;
            $result['code']         =   "501";

        }

    }catch(Exception $e){
        $result['msg']          = $e->getMessage();
        $result['data']         = null;
        $result['success']      = false;
        $result['code']         =   "501";
    }

    $response->write(json_encode($result));
    return $response;
});
/**
 * Update Account
 */
$app->get("/account/update[/{id}]", function($request,$response,$args){
    $appAcc = \models\Account::find_by_sql("SELECT * FROM accounts WHERE app_id='".$args['id']."'");
    $appAcc->balance = 0;
    if($appAcc->update()){
        $result['msg']="Record Successfully Updated";
        $result['data']=$appAcc;
        $result['success']=true;
    }else{
        $result['msg']="Unexpected Error! Record could not be updated";
        $result['data']=$appAcc;
        $result['success']=false;
    }
    $response->write(json_encode($result));
    return $response;
});
/**
 * Create Verifiers
 */
$app->post("/verifiers/create/",function($request,$response,$args){
    try{

        $json = $request->getBody();
        $data = json_decode($json, true);

        $account = new \models\Verifier();

        if(!empty($data)){
            foreach($data as $key=>$val){
                $account->$key = $val;
            }
        }
        $account->created_at =date("Y-m-d H:i:s");
       $route = \models\Route::find_by_sql("SELECT * FROM routes WHERE short_name ='".$data['route_name']."'");

       $account->route_id = $route[0]->id;

        $v =    new system\library\Validator\Validator( array(
            new system\library\Validator\Validate\Required("route_id"," is required "),
            new system\library\Validator\Validate\Required('station_id'," is required"),
            new system\library\Validator\Validate\Required('fname'," is required"),
            new system\library\Validator\Validate\Required('lname'," is required"),
            new system\library\Validator\Validate\Required('phone'," is required"),
            new system\library\Validator\Validate\Required('email'," is required"),
            new system\library\Validator\Validate\Unique('phone'," is already existing","verifiers"),
            new system\library\Validator\Validate\Unique('email'," is already existing","verifiers"),
            new system\library\Validator\Validate\Unique("app_id","is already existing","verifiers"),

        ),$data);
        if($v->execute() == true){

            if($account->create()){

                $result['success']      =   true;
                $result['msg']          =   "Account Created on server Successfully";
                $result['data']           =   $account->id;
                $result['code']         =   "200";

            }else{
                //$result                 =   array();
                $result['success']      =   false;
                $result['msg']       =   " Unexpected Error! Account could not be created. Please try again ";
                $result['code']         =   "501";
                //throw new \Exception("Customer could not be created"); //return "error"; //unsuccessful

            }
        }else{
            $v_result = $v->getErrors();

            $result['success']      =   false;
            $result['msg']       =   $v_result;
            $result['code']         =   "501";

        }

    }catch(Exception $e){
        $result['msg'] = $e->getMessage();
        $result['data']=null;
        $result['success']      =   false;
    }

    $response->write(json_encode($result));
    return $response;
});
/**
 * Update Verifiers
 */
$app->get("/verifiers/update[/{id}]", function($request,$response,$args){
    $appAcc = \models\Verifier::find_by_sql("SELECT * FROM verifiers WHERE app_id='".$args['id']."'");
    $appAcc->balance = 0;
    if($appAcc->update()){
        $result['msg']="Record Successfully Updated";
        $result['data']=$appAcc;
        $result['success']=true;
    }else{
        $result['msg']="Unexpected Error! Record could not be updated";
        $result['data']=$appAcc;
        $result['success']=false;
    }
    $response->write(json_encode($result));
    return $response;
});
/**
 * List Routes
 */
$app->get('/route/index',function($request,$response,$args){
    $rer  = \models\Route::all();
    $response->write(json_encode($rer));
    return $response;
});
/**
 * Create routes
 */
$app->post("/route/create/",function($request,$response,$args){
    try{

        $json = $request->getBody();
        $data = json_decode($json, true);
        $route = new \models\Route();
        //$ticketing = json_decode($args['ticketing']);
        if(!empty($data)){
            foreach($data as $key=>$val){
                $route->$key = $val;
            }
        }
        $route->created_at =date("Y-m-d H:i:s");

        $v =    new system\library\Validator\Validator( array(
            new system\library\Validator\Validate\Unique("short_name","is already existing","merchants"),
            new system\library\Validator\Validate\Required('name'," is required"),

            new system\library\Validator\Validate\Required('short_name'," is required")
        ),$data);
        if($v->execute() == true){

            if($route->create()){

                // $result                 =   array();
                $result['success']          =   true;
                $result['msg']              =   "Record Created";
                $result['data']             =   $route->id;
                $result['code']             =   "200";

            }else{
                //$result                 =   array();
                $result['success']          =   false;
                $result['msg']              =   "Route could not be created";
                $result['code']             =   "501";
                //throw new \Exception("Customer could not be created"); //return "error"; //unsuccessful

            }
        }else{
            $v_result = $v->getErrors();
            $result['success']      =   false;
            $result['msg']       =   $v_result;
            $result['code']         =   "501";

        }


    }catch(Exception $e){
        $result['msg'] = $e->getMessage();
        $result['data']=null;
        $result['success']      =   false;
    }

    $response->write(json_encode($result));
    return $response;
});
/**
 * List Stations
 */
$app->get('/terminals/index',function($request,$response,$args){
    $rer=\models\Terminal::all();
    $response->write(json_encode($rer));
    return $response;
});
/**
 * Get Station Data by id
 */
$app->get('/terminals/data[/{id}]',function($request,$response,$args){
    $rer  = \models\Terminal::find($args['id']);
    $response->write(json_encode($rer));
    return $response;
})->setArgument('id', '1');

$app->get('/hello[/{name}]', function ($request, $response, $args) {
    $response->write("Hello, " . $args['name']);
    return $response;
})->setArgument('name', 'World!');

$app->run();
