<?php
namespace App\Http\Controllers\Api; use App\Http\Controllers\Controller; use Illuminate\Http\Request;
class NotificationController extends Controller { public function index(Request $r){return response()->json($r->user()->notifications()->latest()->paginate(30));} public function read(Request $r,string $id){$n=$r->user()->notifications()->findOrFail($id);$n->markAsRead();return response()->json(['message'=>'Notification lue.']);} public function readAll(Request $r){$r->user()->unreadNotifications->markAsRead();return response()->json(['message'=>'Notifications marquées comme lues.']);}}
