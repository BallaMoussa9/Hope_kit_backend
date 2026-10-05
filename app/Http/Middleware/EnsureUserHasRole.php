<?php
namespace App\Http\Middleware;
use Closure; use Illuminate\Http\Request; use Symfony\Component\HttpFoundation\Response;
class EnsureUserHasRole {
 public function handle(Request $request, Closure $next, string ...$roles): Response {
  $user=$request->user(); if(!$user)return response()->json(['message'=>'Non authentifié.'],401); if(!$user->is_active)return response()->json(['message'=>'Votre compte a été désactivé.'],403);
  $ok=in_array($user->role,$roles,true) || (method_exists($user,'hasAnyRole') && $user->hasAnyRole($roles));
  if(!$ok && method_exists($user,'can')) { foreach($this->permissionsFor($request) as $permission){if($user->can($permission)){$ok=true;break;}} }
  if(!$ok)return response()->json(['message'=>'Accès refusé : le rôle attribué ne possède pas la permission requise.'],403); return $next($request);
 }
 private function permissionsFor(Request $r):array { $p=$r->path(); if(str_contains($p,'/dashboard/inventory')||str_contains($p,'/warehouses'))return $r->isMethod('GET')?['stock.view','warehouses.manage','products.manage']:['stock.manage','warehouses.manage','products.manage']; if(str_contains($p,'/dashboard/documents'))return $r->isMethod('GET')?['documents.view']:['documents.manage']; if(str_contains($p,'/dashboard/payments'))return $r->isMethod('GET')?['payments.view']:['payments.manage']; if(str_contains($p,'/dashboard/kits'))return ['kits.manage']; if(str_contains($p,'/dashboard/reports'))return ['reports.view']; if(str_contains($p,'/dashboard'))return ['dashboard.view']; if(str_contains($p,'/mobile/kits')||str_contains($p,'/mobile/beneficiaries')||str_contains($p,'/mobile/sync'))return ['kits.manage']; return []; }
}
