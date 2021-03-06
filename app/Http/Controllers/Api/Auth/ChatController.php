<?php

namespace App\Http\Controllers\Api\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Tutor;
use App\Message;
use App\Student;
use Carbon\Carbon;
use App\Mega;
use Storage;



class ChatController extends Controller
{


  function NewGuid() {
      $s = strtoupper(md5(uniqid(rand(),true)));
      $guidText =
          substr($s,0,8) . '-' .
          substr($s,8,4) . '-' .
          substr($s,12,4). '-' .
          substr($s,16,4). '-' .
          substr($s,20);
      return $guidText;
  }


public function unique_multidim_array($array, $key) {
    $temp_array = array();
    $i = 0;
    $key_array = array();

    foreach($array as $val) {
        if (!in_array($val[$key], $key_array)) {
            $key_array[$i] = $val[$key];
            $temp_array[$i] = $val;
        }
        $i++;
    }
    return $temp_array;
}

    public function getFriendLists(Request $request){

$authuser = JWTAuth::toUser(JWTAuth::getToken());



        if ($authuser->role == 'tutor'){





          $students = array();

          for($i=0; $i<count($authuser->my_students_id); $i++){



            array_push($students, Mega::where('idx', $authuser->my_students_id[$i])->get());
}
        $friendslists = $students;


        $friendLatestMessage = $students;

        $tempArray = [];
              for($i=0; $i<count($friendLatestMessage); $i++){

              array_push($tempArray, $friendLatestMessage[$i][0]);
              }


              $friendLatestMessage = $tempArray;
              usort($friendLatestMessage, function($a1, $a2) {
                 $value1 = strtotime($a1['updated_at']);
                 $value2 = strtotime($a2['updated_at']);
                 return $value2 - $value1;
              });


        $friendslists = $tempArray;
        $arrayCount = count($friendslists);
        $allUnread = [count($friendslists)];
        $latestMassage = [];

        for($i=0; $i<$arrayCount; $i++){

          $messages = count(Message::where('tutors_id', $authuser->id)->where('student_id', $friendslists[$i]['id'])->where('read', 0)->where('from_student', 1)->get());
          $latestMassageLists =  Message::where('tutors_id', $authuser->id)->where('student_id', $friendslists[$i]['id'])->orderBy('created_at', 'DESC')->limit(40)->get();

     array_push($allUnread, $messages);
     array_push($latestMassage, $latestMassageLists);

        }

        $newLatestMessage = [];
        $extraArray = [];

        for($i=0; $i<$arrayCount; $i++){

        $newData = $this->unique_multidim_array($latestMassage[$i], 'name');

          array_push($newLatestMessage,$newData);
            $secondArray = $newLatestMessage[$i];
          for($m=0; $m<count($secondArray); $m+=7){

            array_push($extraArray, $newLatestMessage[$i][$m]);

          }
        }

        }

        else{

          $tutors = array();

          for($i=0; $i<count($authuser->my_tutors_id); $i++){

            array_push($tutors, Mega::where('idx', $authuser->my_tutors_id[$i])->get());

          }

          $friendslists = $tutors;


          $tempArray = [];
                for($i=0; $i<count($friendslists); $i++){

                array_push($tempArray, $friendslists[$i][0]);
                }

                          $friendLatestMessage = $tempArray;

          usort($friendLatestMessage, function($a1, $a2) {
             $value1 = strtotime($a1['updated_at']);
             $value2 = strtotime($a2['updated_at']);
             return $value2 - $value1;
          });


                  $friendslists = $tempArray;
                  $arrayCount = count($friendslists);
                  $allUnread = [count($friendslists)];
                  $latestMassage = [];


          for($i=0; $i<$arrayCount; $i++){

            $messages = count(Message::where('student_id', $authuser->id)->where('tutors_id', $friendslists[$i]['id'])->where('read', 0)->where('from_tutor', 1)->get());
            $latestMassageLists = Message::where('student_id', $authuser->id)->where('tutors_id', $friendslists[$i]['id'])->orderBy('id', 'DESC')->limit(40)->get();

       array_push($allUnread, $messages);
            array_push($latestMassage, $latestMassageLists);

          }

          $newLatestMessage = [];
          $extraArray = [];

          for($i=0; $i<$arrayCount; $i++){

          $newData = $this->unique_multidim_array($latestMassage[$i], 'name');

            array_push($newLatestMessage,$newData);
              $secondArray = $newLatestMessage[$i];
            for($m=0; $m<count($secondArray); $m+=7){

              array_push($extraArray, $newLatestMessage[$i][$m]);

            }
          }



        }


return response()->json(['friendLists' => $friendslists, 'currentName' => $authuser->en_name, 'currentUserId' => $authuser->id, 'role' => $authuser->role, 'unreadMessages' => $allUnread, 'currentUser' => $authuser, 'myLatestMessage' => $extraArray, 'friendLatestMessage' => $friendLatestMessage]);


}


public function initializeData(Request $request){

  $data = $request->socketId;

  $authuser = JWTAuth::toUser(JWTAuth::getToken());

  if($authuser->role == 'tutor'){


    $mySocket = Mega::find($authuser->id);

    if($mySocket->current_conn_id){

    $mySocket->previous_conn_id = $mySocket->current_conn_id;
    $mySocket->current_conn_id = $data;
    }
    else{
      $mySocket->previous_conn_id = $data;
      $mySocket->current_conn_id =  $data;

    }


    $mySocket->save();

  }
  else{

    $mySocket = Mega::find($authuser->id);

    if($mySocket->current_conn_id){

      $mySocket->previous_conn_id = $mySocket->current_conn_id;
      $mySocket->current_conn_id = $data;
    }
    else{
      $mySocket->previous_conn_id = $data;
      $mySocket->current_conn_id =  $data;

    }


    $mySocket->save();

  }




  return response()->json(['current' => $mySocket->current_conn_id, 'previous' => $mySocket->previous_conn_id, 'userId' =>$authuser->id]);
}

public function getCurrentUserId(){

  $authuser = JWTAuth::toUser(JWTAuth::getToken());

  return response()->json(['currentUserId' => $authuser->id, 'role' => $authuser->role, 'current' => $authuser->current_conn_id]);
}


public function saveMessage(Request $request){

$currentUser = JWTAuth::toUser(JWTAuth::getToken());
$user2 = Mega::find($request->secondUser);
$message = $request->message;


if($currentUser->role == 'tutor'){

$newMessage = Message::create([

    'message' => $request->message,
    'avatar' => 'https://scontent.ficn2-1.fna.fbcdn.net/v/t1.0-1/p160x160/29468236_901369833374211_8734349036217171968_n.jpg?_nc_cat=0&oh=f8f7428a3e9e807d58b3ef91ef215062&oe=5B760837',
    'name' => $currentUser->en_name,
    'tutors_id' => $currentUser->id,
    'student_id' => $user2->id,
    'from_tutor' => true
  ]);


  $latestview = Mega::find($currentUser->id);
  $latestview->latestmessage = $request->message;

  $latestview->save();
}

else{
$newMessage =  Message::create([

    'message' => $request->message,
    'avatar' => 'https://scontent.ficn2-1.fna.fbcdn.net/v/t1.0-1/p160x160/29468236_901369833374211_8734349036217171968_n.jpg?_nc_cat=0&oh=f8f7428a3e9e807d58b3ef91ef215062&oe=5B760837',
    'name' => $currentUser->en_name,
    'tutors_id' => $user2->id,
    'student_id' => $currentUser->id,
    'from_student' => true
  ]);


  $latestview = Mega::find($currentUser->id);
  $latestview->latestmessage = $request->message;

  $latestview->save();

}






return response()->json(['id' => $newMessage->id]);


}

public function editMessage(Request $request, $id){

$message = Message::findOrFail($id);
$message->read = true;
$message->from_tutor = false;
$message->from_student = false;
$message->save();

return response()->json($message);

}

public function getUnreadMessages(){


  $authuser = JWTAuth::toUser(JWTAuth::getToken());



  if ($authuser->role == 'tutor'){



  $friendslists = Tutor::with('student')->where('tutor_id', $authuser->id)->get();

  $friendslists = $friendslists[0]->student;
  $arrayCount = count($friendslists);
  $allUnread = [count($friendslists)];
  $myLists = [count($friendslists)];
  for($i=0; $i<$arrayCount; $i++){

    $messages = Message::where('tutors_id', $authuser->id)->where('student_id', $friendslists[$i]['id'])->where('read', 0)->where('from_student', 1)->get();
    $messagesCount = count($messages);
    array_push($myLists, $messages);
    array_push($allUnread, $messagesCount);
  }
  }

  else{

    $friendslists = $authuser->tutor()->get();
    $arrayCount = count($friendslists);

    $allUnread = [count($friendslists)];
    for($i=0; $i<$arrayCount; $i++){

      $messages = Message::where('student_id', $authuser->id)->where('tutors_id', $friendslists[$i]['id'])->where('read', 0)->where('from_tutor', 1)->get();
      $messagesCount = count($messages);
      array_push($myLists, $messages);
      array_push($allUnread, $messagesCount);
    }
  }

  return response()->json(['allUnread' => $allUnread, 'myLists' => $myLists]);

}


public function getMessages(Request $request){


$currentUser = JWTAuth::toUser(JWTAuth::getToken());
$scrollValue = $request->scrollValue;
$user2 = Mega::find($request->secondUser);



if($currentUser->role == 'tutor'){


  $max = true;
  $messages = Message::where('tutors_id', $currentUser->id)->where('student_id', $user2->id)->orderBy('id', 'ASC')->get();

    if(count($messages) - $scrollValue > 21 ){



        $messages = Message::where('tutors_id', $currentUser->id)->where('student_id', $user2->id)->orderBy('id', 'DESC')->take($scrollValue)->get();


        $messages = $messages->reverse()->values();
        $max = false;
}





  $unReadMessages =  Message::where('tutors_id', $currentUser->id)->where('student_id', $user2->id)->where('read', 0)->get();

  for($i=0; $i<count($unReadMessages); $i++){

    $readMessages = Message::find($unReadMessages[$i]->id);
    $readMessages->read = 1;
    $readMessages->save();


  }
}
else{

    $max = true;

    $messages = Message::where('tutors_id', $user2->id)->where('student_id', $currentUser->id)->orderBy('id', 'ASC')->get();

    if(count($messages) - $scrollValue > 21 ){

        $messages = Message::where('tutors_id', $user2->id)->where('student_id', $currentUser->id)->orderBy('id', 'desc')->take($scrollValue)->get();

          $messages = $messages->reverse()->values();
          $max = false;
    }

    $unReadMessages =  Message::where('tutors_id', $user2->id)->where('student_id', $currentUser->id)->where('read', 0)->get();

    for($i=0; $i<count($unReadMessages); $i++){

      $readMessages = Message::find($unReadMessages[$i]->id);
      $readMessages->read = 1;
      $readMessages->save();


    }

}



  return response()->json(['messages' => $messages, 'currentUserName' => $currentUser->en_name, 'secondUserName' => $user2->en_name, 'unreadCount' => count($unReadMessages), 'scrollValue' => $scrollValue, 'max' => $max]);
}


public function testResponse(){
$authuser = JWTAuth::toUser(JWTAuth::getToken());
  $friendslists = Tutor::with('student')->where('tutor_id', $authuser->id)->get();

  $friendslists = $friendslists[0]->student;


  $query = Message::where('tutors_id', $authuser->id)->where('student_id', $friendslists[1]['id'])->orderBy('created_at', 'DESC')->take(40)->get();
return response()->json($query);
}


public function getTmData(Request $request){



  $tmData = $request->myData;

  $obj = json_decode($tmData);

  $arr = (array)$obj;

  $array_values = array_values($arr);





  for($i=0; $i<count($array_values); $i++){


  $secondArray = $array_values[$i];

  for($j=0; $j<count($secondArray); $j++){

  $mega = new Mega;
  $mega->student_idx = $array_values[$i][$j]->student_idx;
  $mega->idx = $array_values[$i][$j]->student_idx;
  $mega->password = bcrypt($array_values[$i][$j]->student_idx);
  $mega->passkey = 'xCuJiPhKF9xC4VpTpJUa6a969W8eRW';
  $mega->student_id = $array_values[$i][$j]->id;
  $mega->kr_name = $array_values[$i][$j]->kr_name;
  $mega->en_name = $array_values[$i][$j]->en_name;

  $mega->chatroute = str_random(30);
  $mega->role = "student";
  $mega->my_tutors_id = array();
  $mega->save();


  $myStudent = Mega::where('teacher_idx', $array_values[$i][$j]->teacher_idx)->get();

  $temp =  $myStudent[0]->my_students_id;

  array_push($temp, $array_values[$i][$j]->student_idx);

  $studentsResult = array_unique($temp);
  $myStudent[0]->my_students_id = $studentsResult;

  $myStudent[0]->save();

  $myTutor = Mega::where('student_idx', $array_values[$i][$j]->student_idx)->get();

  $temp =  $myTutor[0]->my_tutors_id;
   array_push($temp, $array_values[$i][$j]->teacher_idx);

  $tutorsResult = array_unique($temp);
  $myTutor[0]->my_tutors_id = $tutorsResult;

  $myTutor[0]->save();



  }





  }






return response()->json(['tmData' => 'test']);
}


public function uploadImage(Request $request){





  $currentUser = JWTAuth::toUser(JWTAuth::getToken());
  $user2 = Mega::find($request->secondUser);



  $validator = \Validator::make($request->all(),

  ['image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:12000']);

    if ($validator->fails()) {
       return response()->json($validator->errors(), 422);
    }







      $image = $request->file('image');
      $name = $this->NewGuid().'.'.$image->getClientOriginalExtension();

      $destinationPath = public_path('/images');
      $image->move($destinationPath, $name);









  if($currentUser->role == 'tutor'){

  $newMessage = Message::create([

      'message' =>   $name ,
      'avatar' => 'https://scontent.ficn2-1.fna.fbcdn.net/v/t1.0-1/p160x160/29468236_901369833374211_8734349036217171968_n.jpg?_nc_cat=0&oh=f8f7428a3e9e807d58b3ef91ef215062&oe=5B760837',
      'name' => $currentUser->en_name,
      'tutors_id' => $currentUser->id,
      'student_id' => $user2->id,
      'from_tutor' => true,
      'type' => 'image'
    ]);


    $latestview = Mega::find($currentUser->id);
    $latestview->latestmessage = $currentUser->en_name . 'has sent a photo';

    $latestview->save();
  }

  else{
  $newMessage =  Message::create([

      'message' =>   $name ,
      'avatar' => 'https://scontent.ficn2-1.fna.fbcdn.net/v/t1.0-1/p160x160/29468236_901369833374211_8734349036217171968_n.jpg?_nc_cat=0&oh=f8f7428a3e9e807d58b3ef91ef215062&oe=5B760837',
      'name' => $currentUser->en_name,
      'tutors_id' => $user2->id,
      'student_id' => $currentUser->id,
      'from_student' => true,
      'type' => 'image'
    ]);


    $latestview = Mega::find($currentUser->id);
    $latestview->latestmessage = $currentUser->en_name . 'has sent a photo';

    $latestview->save();

  }






  return response()->json(['id' => $newMessage->id, 'image' => $name]);


}

}
