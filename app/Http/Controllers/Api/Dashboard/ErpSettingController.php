<?php
namespace App\Http\Controllers\Api\Dashboard;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ErpSettingController extends Controller
{
 public function currency(){ $row=DB::table('erp_settings')->where('key','currency')->first(); return response()->json($row?json_decode($row->value,true):['code'=>'XOF','symbol'=>'FCFA','label'=>'Franc CFA BCEAO','decimal_places'=>0]); }
 public function updateCurrency(Request $r){ $v=$r->validate(['code'=>'required|string|size:3|alpha|uppercase','symbol'=>'required|string|max:12','label'=>'required|string|max:80','decimal_places'=>'required|integer|min:0|max:4']); $value=['code'=>strtoupper($v['code']),'symbol'=>$v['symbol'],'label'=>$v['label'],'decimal_places'=>$v['decimal_places']]; DB::table('erp_settings')->updateOrInsert(['key'=>'currency'],['value'=>json_encode($value),'updated_by'=>$r->user()->id,'updated_at'=>now(),'created_at'=>now()]); app(\App\Services\CurrencyFormatter::class)->clearCache(); \App\Models\AuditLog::create(['user_id'=>$r->user()->id,'actor_name'=>$r->user()->name,'actor_email'=>$r->user()->email,'action'=>'settings.currency.updated','new_values'=>$value,'ip_address'=>$r->ip(),'user_agent'=>$r->userAgent()]); return response()->json($value); }
}
