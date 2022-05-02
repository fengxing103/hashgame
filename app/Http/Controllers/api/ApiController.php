<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Telegram\Bot\Api;
use Carbon\Carbon;
use IEXBase\TronAPI\Support\Base58;
use GuzzleHttp\Client;
  
class ApiController extends Controller
{
    
   

    //
    public function pushmsg($bot_token,Request $request){
        //https://tgbot.fxroot129.cc/storage/markdown/images/f3ccdd27d2000e3f9255a7e3e2c4880061c5bd6d4a5b1.jpg
        //   return true;
        $input = $request->all();
        $myfile = fopen("newfile.txt", "w") or die("Unable to open file!");

        fwrite($myfile, json_encode($input));
        fclose($myfile);
        if(!isset($input['update_id'])){
            return "code:20001,消息格式错误".json_encode($input);
        }
        
        $bot = DB::table("tg_bot")->where("bot_token",$bot_token)->first();
        if($bot->status == 0){
            return "code:20002 机器人未开启监听";
        }
        
        if(isset($input['message']['chat']['type']) && $input['message']['chat']['type'] == "private"){
            //私聊
            if(isset($input['message']['text'])){
                //文本
                $this->save_chat($input['message']['message_id'],$input['message']['chat']['id'],$input['message']['chat']['username'],$input['message']['chat']['type'],$input['message']['date'],$input['message']['text'],'',$bot->id); 
                $this->send_chat($input['message']['chat']['id'],$input['message']['text'],$bot->id);
                
            }else if(isset($input['message']['photo'])){
                //图片
                 $telegram = new Api($bot_token);
                 $response = $telegram->getFile(['file_id' => $input['message']['photo'][0]['file_id']]);
                 $response = json_decode($response,true);
                 $filepath = 'https://api.telegram.org/file/bot'.$bot_token.'/'.$response['file_path'];
                 $this->save_chat($input['message']['chat']['id'],$input['message']['chat']['username'],$input['message']['chat']['type'],$input['message']['date'],$input['message']['caption']??"",$filepath,$bot->id);    
            }else{
                return "未识别私聊";
            }
            
        }else if(isset($input['message']['chat']['type']) && $input['message']['chat']['type'] == "supergroup"){
            //群组
            
            if(isset($input['message']['text'])){
                //文本
                $this->save_chat($input['message']['chat']['id'],$input['message']['chat']['title'],$input['message']['chat']['type'],$input['message']['date'],$input['message']['text'],'',$bot->id);
                $this->send_chat($input['message']['chat']['id'],$input['message']['text'],$bot->id);
            }else if(isset($input['sticker'])){
                //表情
                 $telegram = new Api($bot_token);
                 $response = $telegram->getFile(['file_id' => $input['message']['sticker']['thumb']['file_id']]);
                 $response = json_decode($response,true);
                 $filepath = 'https://api.telegram.org/file/bot'.$bot_token.'/'.$response['file_path'];
                 $this->save_chat($input['message']['chat']['id'],$input['message']['chat']['title'],$input['message']['chat']['type'],$input['message']['date'],$input['sticker']['emoji']??"",$filepath,$bot->id);    
            }else{
                return "未识别群组";
            }
             
         
        }else if(isset($input['channel_post']['chat']['type']) && $input['channel_post']['chat']['type'] == "channel"){
            //频道
            
            if(isset($input['channel_post']['text'])){
                //文本
                 $this->save_chat($input['channel_post']['chat']['id'],$input['channel_post']['chat']['title'],$input['channel_post']['chat']['type'],$input['channel_post']['date'],$input['channel_post']['text'],$input['channel_post']['chat']['title'],$bot->id); 
                 $this->send_chat($input['channel_post']['chat']['id'],$input['channel_post']['text'],$bot->id);
            }else if(isset($input['channel_post']['photo'])){
                //图片
                 $telegram = new Api($bot_token);
                 $response = $telegram->getFile(['file_id' => $input['channel_post']['photo'][0]['file_id']]);
                 $response = json_decode($response,true);
                 $filepath = 'https://api.telegram.org/file/bot'.$bot_token.'/'.$response['file_path'];
                 $this->save_chat($input['channel_post']['chat']['id'],$input['channel_post']['chat']['title'],$input['channel_post']['chat']['type'],$input['channel_post']['date'],'',$filepath,$bot->id);    
            }else{
                return "未识别频道";
            }
            
            
            
        }else if(isset($input['callback_query'])){
            
            //按钮回调
            
            $this->send_chat($input['callback_query']['message']['chat']['id'],$input['callback_query']['data'],$bot->id);
            
            $this->save_chat($input['callback_query']['message']['message_id'],$input['callback_query']['message']['chat']['id'],$input['callback_query']['message']['chat']['username'],$input['callback_query']['message']['chat']['type'],$input['callback_query']['message']['date'],$input['callback_query']['data'],'',$bot->id); 
            
            
        }else{
            return "未识别";
        }
         
        return true;
       
        
    }
    
    //私聊
    /*保存群或频道
    $message_id 消息id
    $username 用户名、群名
    $type 聊天类型
    $date 时间
    $content 聊天内容
    $images 聊天图片
    $bot_id 机器人id
    */
    protected function save_chat($message_id,$chat_id,$username,$type,$date,$content,$images,$bot_id){
        
       
        $fullNode = new \IEXBase\TronAPI\Provider\HttpProvider('https://api.trongrid.io');
        $solidityNode = new \IEXBase\TronAPI\Provider\HttpProvider('https://api.trongrid.io');
        $eventServer = new \IEXBase\TronAPI\Provider\HttpProvider('https://api.trongrid.io');
        
        try {
            $tron = new \IEXBase\TronAPI\Tron($fullNode, $solidityNode, $eventServer);
        } catch (\IEXBase\TronAPI\Exception\TronException $e) {
            exit($e->getMessage());
        }
        
          DB::table("tg_msg_record")->insert([
            'chat_id'=> $chat_id,
            'bot_id'=>$bot_id,
            'type' => $type,
            'username'=>$username,
            'content'=>$content,
            'images'=>$images,
            'created_at'=>date("Y-m-d H:i:s",$date),
            'updated_at'=>date("Y-m-d H:i:s",$date),
            ]);
            
          if($type == "channel" || $type == "supergroup" || $type == "private"){
              $chat = DB::table("tg_group")->where("chat_id",$chat_id)->first();
              
              if($chat){
                  $res = DB::table("tg_group")->where("id",$chat->id)->update(['title'=>$username]);
              }else{
                  $res = DB::table("tg_group")->insert([
                      'chat_id'=>$chat_id,
                      'bot_id'=>$bot_id,
                      'title'=>strstr($username,"[",true),
                      'created_at'=>date("Y-m-d H:i:s"),
                      'updated_at'=>date("Y-m-d H:i:s"),
                      ]);
              }
             
              if($type == "private"){
                  
                  $validateAddress = $tron->validateAddress($content);
                  
                  if(strpos($content,'/start') !== false){ 
                      $content = str_replace("/start "," ",$content);
                      $content = trim($content);
                      
                      $puser = DB::table("app_user")->where("my_code",$content)->first();
                      
                      if($puser){
                          $user['father_id'] = $puser->id;
                      }else{
                          $user['father_id'] = 0;
                      }
                     
                          $user['address'] = "";
                          $user['create_time'] = date("Y-m-d H:i:s");
                          $user['fenhong_balance'] = 0 ;
                          $user['fenyong_enable'] = "no";
                          $user['jiesuan_enable'] = "yes";
                          $user['level'] = "";
                          $user['tg_name'] = $username;
                          $user['my_code'] = $this->GetRandStr(7);
                          $user['update_time'] = date("Y-m-d H:i:s");
                          $res = DB::table("app_user")->insert($user);
                          if($res){
                              $this->send_chat($chat_id,"注册成功，回复TRX地址即可绑定帐号",$bot_id,$message_id);
                          }else{
                              $this->send_chat($chat_id,"注册失败",$bot_id,$message_id);
                          }
                    }
                if($content == "联系上线"){
                    $user = DB::table("app_user")->where("tg_name",$username)->first();
                    $puser = DB::table("app_user")->where("id",$user->father_id)->first();
                    
                    if($puser){
                        $text = "您的代理上线为 @".$puser->tg_name;
                        $this->send_chat($chat_id,$text,$bot_id,"联系上线");
                    }else{
                        $text = "您没有代理上线";
                        $this->send_chat($chat_id,$text,$bot_id);
                    }
                }
                if($content == "我的推广链接"){
                    $tg_bot = DB::table("tg_bot")->where("status",1)->first();
                    $user = DB::table("app_user")->where("tg_name",$username)->first();
                    $url = "https://t.me/".$tg_bot->username."?start=".$user->my_code;
                    $this->send_chat($chat_id,"您的推广请链接:".$url,$bot_id,"推广链接");
                }  
                
                if($content == "盈亏流水"){
                    $user = DB::table("app_user")->where("tg_name",$username)->first();
                    $today = DB::table("app_order")->whereDay('create_time', date("D"))->where(["from_address"=>$user->address])->count();
                    $ztoday = DB::table("app_order")->whereDay('create_time', date("D",strtotime("-1 day")))->where(["from_address"=>$user->address])->count();
                    $text = "";
                    if($today > 0){
                        $today_trx_touru = DB::table("app_order")->whereDay('create_time', date("D"))->where(["from_address"=>$user->address,"coin_code"=>"TRX"])->sum("put_amount");
                        $today_trx_win = DB::table("app_order")->whereDay('create_time', date("D"))->where(["from_address"=>$user->address,"coin_code"=>"TRX"])->sum("huikuan_amount");
                        
                        $today_usd_touru = DB::table("app_order")->whereDay('create_time', date("D"))->where(["from_address"=>$user->address,"coin_code"=>"USDT"])->sum("put_amount");
                        $today_usd_win = DB::table("app_order")->whereDay('create_time', date("D"))->where(["from_address"=>$user->address,"coin_code"=>"USDT"])->sum("huikuan_amount");
                        
                        $text .= "今日投入".$today_trx_touru."TRX,回款".$today_trx_win."TRX"."\n\r";
                        $text .= "今日投入".$today_usd_touru."USDT,回款".$today_usd_win."USDT"."\n\r";
                    }else{
                        $text .= "今日未参与游戏"."\n\r";
                    }
                    if($ztoday > 0){
                        $Ztoday_usd_touru = DB::table("app_order")->whereDay('create_time', date("D",strtotime("-1 day")))->where(["from_address"=>$user->address,"coin_code"=>"USDT"])->sum("put_amount");
                        $Ztoday_usd_win = DB::table("app_order")->whereDay('create_time', date("D",strtotime("-1 day")))->where(["from_address"=>$user->address,"coin_code"=>"USDT"])->sum("huikuan_amount");
                        $ztoday_trx_touru = DB::table("app_order")->whereDay('create_time', date("D",strtotime("-1 day")))->where(["from_address"=>$user->address,"coin_code"=>"TRX"])->sum("put_amount");
                        $ztoday_trx_win = DB::table("app_order")->whereDay('create_time', date("D",strtotime("-1 day")))->where(["from_address"=>$user->address,"coin_code"=>"TRX"])->sum("huikuan_amount");
                        
                        $text .= "昨日投入".$ztoday_trx_touru."TRX,回款".$ztoday_trx_win."TRX"."\n\r";
                        $text .= "昨日投入".$Ztoday_usd_touru."USDT,回款".$Ztoday_usd_win."USDT"."\n\r";
                    }else{
                        $text .= "昨日未参与游戏"."\n\r";
                    }
                    
                    $this->send_chat($chat_id,$text,$bot_id,"盈亏流水");
                }  
                  if($validateAddress['result']){
                      $count = DB::table("app_user")->where("tg_name",$username)->count();
                      if($count > 0 ){
                          $user['address'] = $content;
                          
                          DB::table("app_user")->where("tg_name",$username)->update($user);    
                          $text = "绑定成功";
                        //   $text = DB::table("tg_keyword")->where("keyword",'like','%'.$content.'%')->first();
                          $this->send_chat($chat_id,$text,$bot_id,$message_id);
                      }else{
                          $text = "您的账户不存在";
                          $this->send_chat($chat_id,$text,$bot_id,$message_id);
                      }
                      
                      
                  }
              }
          }    
    }
    
        public  function GetRandStr($length){
        //字符组合
             $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
             $len = strlen($str)-1;
             $randstr = '';
             for ($i=0;$i<$length;$i++) {
              $num=mt_rand(0,$len);
              $randstr .= $str[$num];
             }
             return $randstr;
        }
    
    /*
    推送消息
    $chat_id 群Id或频道id
    $content 群名称或频道名称
    $bot_id 机器人id
    */
    protected function send_chat($chat_id,$content,$bot_id,$message_id = ""){
        
        
        $tg_keyword = DB::table("tg_keyword")->where("keyword",'like','%'.$content.'%')->where(["bot_id"=>$bot_id,'status'=>1])->first();
        
        if($tg_keyword){
            $bot_token = DB::table("tg_bot")->where("id",$bot_id)->value("bot_token");
            $telegram = new Api($bot_token);
            $encodedKeyboard = '';
            //封装按钮
            
            if($tg_keyword->type == 1){
                    $inline_keyboard = [];
                    $keyboard = ['inline_keyboard'=>[]];
                    $chatid = DB::table("tg_group")->where("chat_id",$chat_id)->value("id");
                    $btns = DB::table("tg_btn")->where(['bot_id'=>$bot_id,'status'=>1])->get();
                    foreach ($btns as $btn){
           
                       $callback_data = DB::table("tg_keyword")->where('id',$btn->keywordid)->value('keyword');
                    //   dd($btn);
                       if($btn->type==0){
                            //内联
                            $btn_data['text'] = $btn->btntext;
                            $btn_data['callback_data'] = $callback_data;
                            
                        } else{
                            $btn_data['text'] = $btn->btntext;
                            $btn_data['url'] = $callback_data;
                        }   
                        
                         array_push($inline_keyboard,$btn_data);
                        $btn_data = [];
                        if(count($inline_keyboard)>=2){
                            array_push($keyboard['inline_keyboard'],$inline_keyboard);
                            $inline_keyboard = [];
                        }
                      
                  }
                  
                  array_push($keyboard['inline_keyboard'],$inline_keyboard);
                  $encodedKeyboard = json_encode($keyboard);
                  
                }
            
            if($tg_keyword->method == "sendphoto"){
                $photo = "https://botadmin.haxiwang.top/storage/".$tg_keyword->file_id;
                $response = $telegram->sendphoto([
                  'chat_id' => $chat_id,
                  'photo' => $photo,
                  'caption' => $tg_keyword->content,
                  'reply_markup'=>$encodedKeyboard,
                ]);
                
            }else{
                
                 $response = $telegram->sendMessage([
                  'chat_id' => $chat_id,
                  'text' => $tg_keyword->content,
                  'reply_markup'=>$encodedKeyboard,
                  ]);     
            }
            
           
           
        }
        $bot_token = DB::table("tg_bot")->where("id",$bot_id)->value("bot_token");
        $telegram = new Api($bot_token);
         if($message_id != ""){
                // dd("2131");
                 $response = $telegram->sendMessage([
                  'chat_id' => $chat_id,
                  'text' => $content,
                  'ReplyKeyboardMarkup'=>$encodedKeyboard ?? "",
                  'reply_to_message_id'=>$message_id,
                  'allow_sending_without_reply'=>true,
                //   'ReplyKeyboardMarkup'=>['keyboard'=>['1','2']]
                  ]);     
            }
         
    }
    
    public function get_romaddress(){
        
       
        // return true;
        //正式环境中最好不要开启 若要在正式环境中开启请替换掉do while结构 该结构虽然获取地址的效率增高 但最使服务器负载运行
        $fullNode = new \IEXBase\TronAPI\Provider\HttpProvider('https://api.trongrid.io');
        $solidityNode = new \IEXBase\TronAPI\Provider\HttpProvider('https://api.trongrid.io');
        $eventServer = new \IEXBase\TronAPI\Provider\HttpProvider('https://api.trongrid.io');
        
        try {
            $tron = new \IEXBase\TronAPI\Tron($fullNode, $solidityNode, $eventServer);
        } catch (\IEXBase\TronAPI\Exception\TronException $e) {
            exit($e->getMessage());
        }
        
        do
        {
            $generateAddress = $tron->GenerateAddress();
            $isValid = $tron->isAddress($generateAddress->getAddress());
            $address_hex = $generateAddress->getAddress();
            $private_key = $generateAddress->getPrivateKey();
            $public_key = $generateAddress->getPublicKey();
            $address_base58 = $generateAddress->getAddress(true);
            echo $address_base58."<br>";
            echo $private_key."<br>";
            $res = $this->checkaddress($address_base58);
        }
        while ($res != 1);
        
        if($res == 1){
            DB::table("app_romaddress")->insert([
                'private_key'=>$private_key,
                'public_key'=>$public_key,
                'address_hex'=>$address_hex,
                'address_base58'=>$address_base58,
                'createtime'=>time(),
                
                ]);
        }
        
    
    
    }
    
    public function checkaddress($address_base58){
       
        $address_base58 = str_split($address_base58, 1);
        if($address_base58[33] == $address_base58[32] && $address_base58[32] == $address_base58[31] && $address_base58[31] == $address_base58[30]){
            return 1;
        }
        
        // if($address_base58[0] == $address_base58[1] && $address_base58[1] == $address_base58[2]){
        //     return 1;
        // }
        
        // if($address_base58[33] == $address_base58[32] && $address_base58[32] == $address_base58[31]){
        //     return 1;
        // }
        

        return 2;
        
    }
    
    public function get_config(){
        return "213";
    }
    
}
