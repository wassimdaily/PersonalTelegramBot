<?php 
include 'connect.php';
ob_start();

$API_KEY = '475432747:AAH07BFi7KYtCNzZtxx5NpSaO9MHhOHkqqg';  // API Token
define('API_KEY',$API_KEY);
function bot($method,$datas=[]){
    $url = "https://api.telegram.org/bot".API_KEY."/".$method;
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$datas);
    $res = curl_exec($ch);
    if(curl_error($ch)){
        var_dump(curl_error($ch));
    }else{
        return json_decode($res);
    }
}


$update     = json_decode(file_get_contents('php://input'));
$message    = $update->message;
$text       = $message->text;
$chat_id    = $message->chat->id;
$first_name = $message->chat->first_name;
$last_name  = $message->chat->last_name;
$username   = $message->chat->username;
$id         = $message->from->id;
$msg_id     = $update->message->message_id;


$reply  = "مرحبا بك  كيف اقدر اخدمك, %fname% %lastname% ";
$reply2 = "اهلين وسهلين بعودتك، %fname% %lastname%";

$replace_var  = str_replace(array('%fname%', '%lastname%'), array($first_name, $last_name), $reply);
$replace_var2 = str_replace(array('%fname%', '%lastname%'), array($first_name, $last_name), $reply2);

$sqluserQuerey = mysqli_query($db,"SELECT * FROM Users WHERE Chat_ID = '$chat_id'");
$fetsql = mysqli_fetch_assoc($sqluserQuerey);
$chatUser = $fetsql['Chat_ID'];

if($text == '/start' and ($chat_id == $chatUser))
    {
mysqli_query($db,"UPDATE Users SET User_Name = '$username', Frist_Name ='$first_name', Last_Name = '$last_name' WHERE Chat_ID = '$chatUser'");
        bot('sendMessage',[
            'chat_id'=>$chat_id,
            'text'=> $replace_var2
        ]);
    }


if($text == '/start' and empty($chatUser))
    {
mysqli_query($db,"INSERT INTO Users(Chat_ID,User_Name,Frist_Name,Last_Name) VALUES ('$chat_id','$username','$first_name','$last_name')");

        bot('sendMessage',[
            'chat_id'=>$chat_id,
            'text'=> $replace_var
        ]);
    }

    
    

if(!empty($text) and !empty($chat_id))
{
  mysqli_query($db, "INSERT INTO conversations (msg_id, Frist_Name,Last_Name,User_Name,Chat_Id,Word) VALUES ('$msg_id','$first_name','$last_name','$username','$id','$text')");
}

$select_db = mysqli_query($db, "SELECT * FROM Words");
$ans = "";

while($rows = mysqli_fetch_assoc($select_db))
{
    if($rows['Word'] == $text )
    {
        bot('sendMessage',[
        'chat_id'=>$chat_id,
        'text'=> $rows['Reply']
        ]);
        $rep = $rows['Reply'];
        mysqli_query($db, "Update conversations SET Reply='$rep' WHERE msg_id='$msg_id'");
    $ans .= true;
    }

}

if($ans != true)
{
    mysqli_query($db, "INSERT INTO temp_msgs (msg_id, chat_id, username, text) VALUES ('$msg_id','$chat_id','$username','$text')");
}

$start_q = mysqli_query($db,"SELECT * FROM Users WHERE chat_id='".$chat_id."'");
//$row = mysqli_fetch_assoc($query);

// search data

$find_element = explode('++',$text);
$find_element1 = $find_element[0];
$find_element2 = $find_element[1];

$find_q = mysqli_query($db,"SELECT * FROM Words WHERE Word='$find_element2'");
$fetch_find = mysqli_fetch_assoc($find_q);
$selectWord = $fetch_find['Word'];
if( ( isset($find_element[1]) and ($find_element[0] == 'find' or $find_element[0] == 'Find') ) and !empty($selectWord))
{
    bot('sendMessage',[
    'chat_id'=>$chat_id,
    'text'=>
    'رقم الكلمه: '.$fetch_find['ID']."\n".
    'الكلمه: '   .$fetch_find['Word']."\n". 
    'الرد: '.$fetch_find['Reply']

    ]);
}
if (empty($selectWord) and isset($find_element[1])) {
    bot('sendMessage',[
    'chat_id'=>$chat_id,
    'text'=>'لا توجد بيانات متطابقه عن الكلمه :'.$find_element[1]
    ]);
}
// add word and replay to the data

$explode = explode("$$",$text);
if(isset($explode[1]))
{

          $w = $explode[0];
          $r = $explode[1];
          $sql = mysqli_query($db,"INSERT INTO Words(Word,Reply) Values ('$w','$r')");
}

if($sql)
{

        bot('sendMessage',
        [
            'chat_id'=>$chat_id,
            'text'=>' تم إضافه الكلمه: '. '{ ' .$w .' }' . ' و الرد المقابل لها: ' .'{ '  .$r. ' }'  .' إلى قاعده البيانات'

        ]);


}
// edit words or reply or all in one 
$expEdit      = explode('##', $text);
$explodeWord  = $expEdit[0];
$explodeReply = $expEdit[1];
$explodeID    = $expEdit[2];

if (isset($explodeWord) and isset($explodeReply)) {
    $selectOld = mysqli_query($db,"SELECT * FROM Words WHERE ID = '$explodeID'");
    $fetchOld = mysqli_fetch_assoc($selectOld);
    $oldWord = $fetchOld['Word'];
    $oldReply = $fetchOld['Reply'];
    
//,old_word,old_reply,new_word,new_reply
//,'$oldWord','$oldReply',$explodeWord','$explodeReply'
    $insertUpdatedWords = mysqli_query($db,"INSERT INTO Updated (id_word,old_word,old_reply,new_word,new_reply) VALUES ('$explodeID','$oldWord','$oldReply','$explodeWord','$explodeReply') ");

    $updateWords = mysqli_query($db,"UPDATE Words SET Word = '$explodeWord', Reply ='$explodeReply' WHERE ID = '$explodeID'");
}

    //send done updated
if ($updateWords) {
    bot('sendMessage',[
    'chat_id'=>$chat_id,
    'text'=>'تم التحديث بنجاح'
    ]);
}
// delete words and reply by id of the word and reply

$expDelete = explode('++',$text);
$delCommand = $expDelete[0];
$idWord = $expDelete[1];

if( ( isset($idWord) and ($delCommand == 'del') ) )


    {
            $selectDeletedWords = mysqli_query($db,"SELECT * FROM Words WHERE ID = $idWord");
            $fetchQuery = mysqli_fetch_assoc($selectDeletedWords);
            $woord = $fetchQuery['Word'];
            $reeply = $fetchQuery['Reply'];
            $insertDeletedWord = mysqli_query($db,"INSERT INTO Deleted(word,reply) VALUES('$woord','$reeply')");
            $delQuery = mysqli_query($db,"DELETE FROM Words WHERE ID = $idWord ");

                    bot('sendMessage',[
                    'chat_id'=>$chat_id,
                    'text'=>'تم حذف الكلمه رقم: { '  .$fetchQuery['ID'].' الكلمه: '.$fetchQuery['Word'].' الرد المقابل لها : '.$fetchQuery['Reply'].' }'
                      ]);
          
            }    
 

// add new admin of bot

$explode = explode("<>",$text);
if(isset($explode[1]))
{

          $fristName = $explode[0];
          $lastName  = $explode[1];
          $chatID    = $explode[2];
          $insertAdmin = mysqli_query($db,"INSERT INTO admins(frist_name,last_name,chat_id) Values ('$fristName','$lastName','$chatID')");
}

//show message about done added the new admin

if($insertAdmin)
{

        bot('sendMessage',
        [
            'chat_id'=>$chat_id,
            'text'=>' تم تعيين : '. '{ ' .$fristName .' }' . '  ' .'{ ' .$lastName. ' }' .' مدير جديد '.'  إلى قاعده مدراء البوت'

        ]);


}

//show my chat id

if($text == 'my id'){
    
        bot('sendMessage',
        [
            'chat_id'=>$chat_id,
            'text'=>$chat_id

        ]);
}