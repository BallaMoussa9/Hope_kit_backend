<?php
namespace App\Http\Controllers\Api\Dashboard;
use App\Http\Controllers\Controller; use App\Models\Supplier; use App\Models\Customer; use Illuminate\Http\Request; use Illuminate\Support\Str;
class PartnerController extends Controller {
 public function suppliers(){return response()->json(Supplier::latest()->get());}
 public function storeSupplier(Request $r){$v=$r->validate(['name'=>'required|string|max:255','contact_name'=>'nullable|string|max:255','phone'=>'nullable|string|max:50','email'=>'nullable|email|max:255','address'=>'nullable|string','tax_number'=>'nullable|string|max:100']);return response()->json(Supplier::create($v),201);}
 public function updateSupplier(Request $r,Supplier $supplier){$v=$r->validate(['name'=>'required|string|max:255','contact_name'=>'nullable|string|max:255','phone'=>'nullable|string|max:50','email'=>'nullable|email|max:255','address'=>'nullable|string','tax_number'=>'nullable|string|max:100','is_active'=>'boolean']);$supplier->update($v);return response()->json($supplier);}
 public function customers(){return response()->json(Customer::withCount('documents')->latest()->get());}
 public function storeCustomer(Request $r){$v=$r->validate(['name'=>'required|string|max:255','contact_name'=>'nullable|string|max:255','phone'=>'nullable|string|max:50','email'=>'nullable|email|max:255','address'=>'nullable|string','tax_number'=>'nullable|string|max:100']);$v['customer_code']='CLI-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));return response()->json(Customer::create($v),201);}
 public function updateCustomer(Request $r,Customer $customer){$v=$r->validate(['name'=>'required|string|max:255','contact_name'=>'nullable|string|max:255','phone'=>'nullable|string|max:50','email'=>'nullable|email|max:255','address'=>'nullable|string','tax_number'=>'nullable|string|max:100','is_active'=>'boolean']);$customer->update($v);return response()->json($customer);}
}
