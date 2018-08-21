<?php
/**
 * Created by PhpStorm.
 * User: 18710
 * Date: 2018/8/15
 * Time: 15:33
 */

namespace App\Http\Controllers;


namespace App\Http\Controllers;
use App\Http\Requests;
use Request;
use Illuminate\Support\Facades\DB;
class IndentController extends Controller {

    public function detail()
    {
        if (session()->has('username')) {
            $pindex =Request::input('username',1);


            return view('detail',[

            ]);
        }else{
            return view('log',[
                'faild'=>"请登录！",
            ]);
        }
    }


    public function index()
    {
        $requestAll=Request::all();
        if (session()->has('username')) {
        $sql = "select *,sources.id AS sourceId,SUBSTRING_INDEX(SUBSTRING_INDEX(scene_str,'_',2),'_',-1) as qian ,SUBSTRING_INDEX(scene_str,'_',-1) as uuid from sources LEFT JOIN sales_amount ON sources.id = sales_amount.sourcesId LEFT JOIN services on services.id = type_id WHERE scene_str LIKE 'panopath_xiaoshou_%'";
        $pindex =Request::input('pageIndex',1);
        $star = 8*($pindex-1);


        $serviceType =Request::input('service_type',"0");
        if ($serviceType=="1"){
            $sql=$sql." and type_id != 5 ";
        }else if ($serviceType=="2"){
            $sql=$sql." and type_id = 5 ";
        }else if ($serviceType=="3"){
            $sql=$sql." and type_id is null ";
        }


        $username =Request::input('userName',null);
        if ($username!=null){
            $cusername=DB::select("SELECT QRuuid from qrcodes where username ='".$username."'");

            if (sizeof($cusername)>0){
             for($i=0; $i<sizeof($cusername); $i++) {
                    if ($i==0){
                        $sql=$sql." and ( scene_str like '%".$cusername[$i]->QRuuid."%'";
                    }else{
                        $sql=$sql." or scene_str like '%".$cusername[$i]->QRuuid."%'";
                    }

                   if ($i==(sizeof($cusername)-1)){
                        $sql=$sql.")";
                    }
            }
          }else{
                $sql=$sql." and scene_str like '无匹配'";
            }
        }
        $sql=$sql." limit  $star ,8";
        $qrcode = DB::select($sql);

        $countsql = str_replace("*","count(*) as cou",$sql);
        $count = DB::select($countsql)[0]->cou;
        $pageend =$count%8==0?$count/8:$count/8+1;

        for($i=0; $i<sizeof($qrcode); $i++) {
                $usernames = DB::select("SELECT userName from qrcodes where qrcodes.QRuuid ='".$qrcode[$i]->uuid."'");
                if (isset($usernames[0]->userName)){
                    $qrcode[$i]->username = $usernames[0]->userName;
                }else{
                    $qrcode[$i]->username = "无对应销售";
                }
        }
            $servicesTypeList = DB::select("select * from services");
            return view('listPage',[
                'qrcode'=>$qrcode,
                'requestAll'=>$requestAll,
                'pageend'=>floor($pageend),
                'pageindex'=>$pindex,
                'serviceType'=>$serviceType,
                'servicesTypeList'=>$servicesTypeList,
            ]);
        }else{
            return view('log',[
                'faild'=>"请登录！",
            ]);
        }
            //


    }

        public function store()
       {
           $requestAll=Request::all();
           $typeId= $requestAll['typeId'];
           $sourceId=$requestAll['sourceId'];
           $servicesType = DB::select("select * from services where id = ".$typeId)[0];

           $username= $requestAll['username'];
           $money=$servicesType->money;
           $bonusMoney=$servicesType->money*$servicesType->bonus_rate;

          $bool=DB::insert("insert into sales_amount(money,type_id,bonus_money,sourcesId,userName)
			values(?,?,?,?,?)",[$money,$typeId,$bonusMoney,$sourceId,$username]);
           return redirect('list');
       }
}