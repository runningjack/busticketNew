<?php
/**
 * Created by PhpStorm.
 * User: Amedora
 * Date: 12/10/15
 * Time: 10:38 PM
 */
ini_set('error_reporting', E_ALL);
ini_set("display_errors","1");
ini_set('max_execution_time', 0);
ini_set('memory_limit', '-1');
?>
<?php
require 'vendor/autoload.php';
require 'config.php';

function connect_db() {
    $server = 'localhost'; // this may be an ip address instead
    $user = 'root';
    $pass = '';
    $database = 'busticket';
    $connection = new mysqli($server, $user, $pass, $database);

    return $connection;
}

$app = new Slim\App(array(
    'debug' => true,'log.enabled' => true,
));


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
    if(!$rer){
        $rer=array();
    }
    $response->write(json_encode($rer));
    return $response;
});

/**
 * List Tickets
 */

$app->get('/tickets/index',function($request,$response,$args){
    $rer  = \models\Tickets::all();
    if(!$rer){
        $rer=array();
    }
    $response->write(json_encode($rer));
    return $response;
});

/**
 * Update tickets
 */

$app->get('/tickets/update[/{id}/{status}]', function($request,$response,$args){
    $ticket  = \models\Tickets::find($args['id']);
    $ticket->status = $args['status'];
    $ticket->created_at = date("Y-m-d H:i:s");
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

$app->post('/tickets/batchsync[/{appid}]',function($request,$response,$args){
    $batchTickets = \models\Tickets::find_by_sql("SELECT * FROM tickets WHERE status=2 AND app_id ='".$args['appid']."'");
    $json = $request->getBody();

    $json =str_replace("\\","",$json);
    $json =str_replace("\"[","[",$json);
    $json =str_replace("]\"","]",$json);
    $datas = json_decode($json,true);


   //$myTicket = new \models\Ticket();
    //$ticketing = json_decode($args['ticketing']);
    $k=1;
    $verifiedTicket = array();
   foreach($datas as $data){

        $myTicket = \models\Tickets::find($data['id']);

        if($myTicket->status == 2){
            array_push($verifiedTicket,$myTicket) ;
        }elseif($myTicket->status == 1){

        }elseif($myTicket->status == 0){
            $myTicket->status =1;
            $myTicket->created_at = date("Y-m-d H:i:s");
            $myTicket->update();
        }

        $k++;
    }


    $result=[];
    if(count($verifiedTicket)>0){
        $result['success']  =true;
        $result['data']     =$verifiedTicket;
        $result['msg']      ="Data Updated";
        $result['code']     ="200";
    }else{
        //c
        $result['success']  =true;
        $result['data']     =$verifiedTicket;
        $result['msg']      ="Data Updated";
        $result['code']     ="200";
    }

   /* $myFile = "javafile.txt";
    $fh = fopen($myFile, 'w') or die("can't open file");

    fwrite($fh, json_encode($result));

    fclose($fh);*/


    $response->write(json_encode($result));
    return $response;
});

//Gets all tickets relating to a particular
//route to a varifiers on a particular route
$app->get("/tickets/verifier/[/{route_id}]",function($request,$response,$args){
    $myTickets  = \models\Tickets::find_by_sql("SELECT * FROM tickets WHERE route_id =".$args['route_id']);


    if($myTickets){
        $result['success']  =true;
        $result['data']     =$myTickets;
        $result['msg']      ="Data Updated";
        $result['code']     ="200";

    }else{

        $result['data']     =null;
        $result['msg']      ="failed";
        $result['success']  =false;
        $result['code']     ="501";
    }
    if(!$myTickets){
        $myTickets=array();
    }

    $response->write(json_encode($myTickets));
    return $response;
});
/**
 * Get Tickets by appid
 */
$app->get('/tickets/data[/{appid}]',function($request,$response,$args){
    $myTickets  = \models\Tickets::find_by_sql("SELECT * FROM tickets WHERE app_id ='".$args['appid']."'  AND status=0 LIMIT 1000");
    $db = connect_db();
    $result = $db->query("SELECT * FROM tickets WHERE app_id ='".$args['appid']."'  AND status=0 LIMIT 1000" );
    while ( $row = $result->fetch_array(MYSQLI_ASSOC) ) {
        $data[] = $row;
    }


    if(count($data)>0){
        $result['success']  =true;
        $result['data']     =$myTickets;
        $result['msg']      ="Data Updated";
        $result['code']     ="200";
        /*foreach($myTickets as $ticket){
            $ticket->download_status = 1;
            //$ticket->update();
        }*/

    }else{

        $result['data']     =array();
        $result['msg']      ="failed";
        $result['success']  =false;
        $result['code']     ="501";
    }


    $response->write(json_encode($result));
    return $response;
})->setArgument('id', '1');

$app->get('/ticketing/verify[/{id}/{inspectid}]',function($request,$response,$args){

    $ticketing = \models\Ticketing::getTicketingByTicketID($args['id']);
    //print_r($ticketing);
    $ticket = \models\Tickets::find($args['id']);

    if($ticketing){ // check if ticket exits
        if($ticketing->status == 0){
            /* return error ticket not issued */
            $ticketing->status  = 1;
            $ticketing->agent_id = $args['inspectid'];
            if($ticketing && $ticket){
                //update ticketing data details
                if($ticketing->update()){
                    $ticket->update();
                    $msg = "Access Granted  Ticket is Valid";
                }
            }else{
                $msg ="Unexpected Error";
            }
        }elseif($ticketing->status == 1){
            // return
            $msg = "Access Denied Ticket already used";
        }else{
            $msg = "Invalid Ticket";
        }
    }else{
        /* return ticket is invalid */
        $msg = "Invalid Ticket";
    }
    $response->write($msg);

    return $response;
});
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
        $myTicketing->created_at = date("Y-m-d H:i:s");
        if($myTicketing->create()){
            $ticket = \models\Tickets::find($myTicketing->ticket_id);
            $ticket->status=1;
            $ticket->updated_at = date("Y-m-d H:i:s");
            $ticket->update();

            $acc = \models\Account::find_by_sql("SELECT * FROM accounts WHERE app_id ='".$data['app_id']."'");
            $acc[0]->balance -= $data['amount'];
            $acc[0]->updated_at = date("Y-m-d H:i:s");
            $acc[0]->update();
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


//Logout Account
$app->get("/account/login[/{id}]", function($request,$response,$args){
    $appAcc = \models\Account::find_by_sql("SELECT * FROM accounts WHERE app_id='".$args['id']."'");
    $appAcc->is_logged_in = 1;
    if($appAcc->update()){
        $msg="Logged In";
    }else{
        $msg="Unexpected error";
    }
    $response->write(($msg));
    return $response;
});

//Logout Account
$app->get("/account/logout[/{id}]", function($request,$response,$args){
    $appAcc = \models\Account::find_by_sql("SELECT * FROM accounts WHERE app_id='".$args['id']."'");
    $appAcc->is_logged_in = 0;
    if($appAcc->update()){
        $msg = "logged out";
    }else{
        $msg = "Unexpected Error";
    }
    $response->write(($msg));
    return $response;
});
//get Account balance
$app->get("/account/balancesync[/{appid}]",function($request,$response,$args){
    $appAcc = \models\Account::find_by_sql("SELECT * FROM accounts WHERE app_id='".$args['appid']."'");

    $msg = $appAcc[0]->balance;
    $response->write($msg);
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
    if($rer == false){
        $rer=array();
    }
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

$app->get('/driver/data[/{licence_no}]',function($request,$response,$args){
    //$rer  = \models\Driver::findUniqueByColumn("licence_no",$args['licence_no']);findUniqueByColumn("licence_code",$args['licence_no']);//
    $rer = \models\Driver::find_by_sql("SELECT * FROM drivers WHERE licence_code ='".$args['licence_no']."'");
    if($rer){
        
        $result['success']          =   true;
        $result['msg']              =   "Record Created";
        $result['data']             =   $rer;
        $result['code']             =   "200";

    }else{
        //$result                   =   array();
        $result['success']          =   false;
        $result['msg']              =   "Driver record does not exist";
        $result['data']             =   new \models\Driver();
        $result['code']             =   "501";
    }
    $response->write(json_encode($result));
    return $response;
})->setArgument('id', '1');

$app->post('/driver/update[/{licence_no}]',function($request,$response,$args){
    //$rer  = \models\Driver::findUniqueByColumn("licence_no",$args['licence_no']);findUniqueByColumn("licence_code",$args['licence_no']);//
    $data = $request->getBody();
    $data = json_decode($data,true);
    $rer = \models\Driver::find_by_sql("SELECT * FROM drivers WHERE licence_code ='".$args['licence_no']."'");


    if($rer){
        $rer[0]->password = $data['password'];
        $rer[0]->bus_id = $data['bus_id'];
        $rer[0]->route_id = $data['route_id'];
        $rer[0]->verified = 1;
        $rer[0]->app_id = $data['app_id'];
        $rer[0]->is_logged_in =1;
        $rer[0]->update();
        $result['success']          =   true;
        $result['msg']              =   "Record Created";
        $result['data']             =   $rer;
        $result['code']             =   "200";

    }else{

        $result['success']          =   false;
        $result['msg']              =   "Driver record does not exist";
        $result['data']             = new \models\Driver();
        $result['code']             =   "501";
    }
    $response->write(json_encode($result));
    return $response;
})->setArgument('id', '1');


$app->post("/triplog/create[/{appid}]",function($request,$response,$args){
    $data = $request->getBody();
    $data = json_decode($data,true);


    if(!empty($data)){
        $trip = new \models\Triplog();
        foreach($data as $key=>$val){
            $trip->$key = $val;
        }
    }

    $trip->created_at =date("Y-m-d H:i:s");

    $v =    new system\library\Validator\Validator( array(
        new system\library\Validator\Validate\Required("driver_id","driver id required"),
        new system\library\Validator\Validate\Required('bus_id'," is required"),
        new system\library\Validator\Validate\Required('route_id'," is required"),
        new system\library\Validator\Validate\Required('service_id'," is required")
    ),$data);

    $result['success']          =   false;
    $result['msg']              =   "";
    $result['data']             =   $trip;
    $result['code']             =   "404";

    if($v->execute() == true){
        if($trip->create()){

            $result['success']          =   true;
            $result['msg']              =   "Record Created";
            $result['data']             =   $trip;
            $result['code']             =   "200";

        }else{

            $result['success']          =   false;
            $result['msg']              =   "Route could not be created";
            $result['data']             =   $trip;
            $result['code']             =   "501";
            //throw new \Exception("Customer could not be created"); //return "error"; //unsuccessful

        }
    }else{
        $v_result = $v->getErrors();
        $result['success']      =   false;
        $result['msg']          =   $v_result;
        $result['data']         =   $trip;
        $result['code']         =   "501";

    }



    $response->write(json_encode($result));
    return $response;

});


$app->post('/triplog/batchsync',function($request,$response,$args){
   // $batchTickets = \models\Tickets::find_by_sql("SELECT * FROM tickets WHERE status=2 AND app_id ='".$args['appid']."'");
    try{
        $json = $request->getBody();


        $json =str_replace("\\","",$json);
        $json =str_replace("\"[","[",$json);
        $json =str_replace("]\"","]",$json);
        $datas = json_decode($json,true);
       // print_r($datas);
        //exit;

        //$myTicket = new \models\Ticket();
        //$ticketing = json_decode($args['ticketing']);
        $k=1;
        $verifiedTicket = array();
        foreach($datas as $data){
            $myNewTrip = new \models\Triplog();
            $myTrip = \models\Triplog::findUniqueByColumn("trip_id",$data['trip_id']);//("SELECT * FROM fleetlogs WHERE trip_id='$data[trip_id]'");

            if(isset($myTrip->trip_id)){
                foreach($data as $key=>$val){
                    $myTrip->$key =$val;
                }
                $myTrip->update();
                array_push($verifiedTicket,$myTrip);

            }else{
                $myNewTrip->create();
                array_push($verifiedTicket,$myNewTrip);
            }


            $k++;
        }


        $result=[];
        if(count($verifiedTicket)>0){
            $result['success']  =true;
            $result['data']     =$verifiedTicket;
            $result['msg']      ="Data Synchronized to server";
            $result['code']     ="200";
        }else{
            //c
            $result['success']  =true;
            $result['data']     =$verifiedTicket;
            $result['msg']      ="Data Synchronized to server";
            $result['code']     ="200";
        }
    }catch(Exception $ex){
        $result['success']  =false;
        $result['data']     =$verifiedTicket;
        $result['msg']      =$ex->getMessage();
        $result['code']     ="501";
    }

    /* $myFile = "javafile.txt";
     $fh = fopen($myFile, 'w') or die("can't open file");

     fwrite($fh, json_encode($result));

     fclose($fh);*/


    $response->write(json_encode($result));
    return $response;
});

$app->run();
