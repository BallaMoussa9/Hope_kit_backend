<?php
namespace App\Services;

use App\Models\CommercialDocument;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;

/** Dependency-free PDF generator for printable HOPE ERP documents. */
class SimplePdfService
{
    private array $pages=[]; private array $current=[]; private float $y=800; private string $title='';
    private function clean(string $s): string { $s=str_replace(["\r","\n","\t"],[' ',' ',' '],$s); $c=@iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s); return preg_replace('/[^\x20-\x7E]/','',$c!==false?$c:$s)??''; }
    private function esc(string $s): string { return str_replace(['\\','(',')'],['\\\\','\\(','\\)'],$s); }
    private function cmd(string $c): void {$this->current[]=$c;}
    private function text(float $x,float $y,string $txt,float $size=9,bool $bold=false,string $rgb='0 0 0'): void { $font=$bold?'/F2':'/F1'; $this->cmd("{$rgb} rg BT {$font} {$size} Tf 1 0 0 1 {$x} {$y} Tm (".$this->esc($this->clean($txt)).") Tj ET"); }
    private function line(float $x1,float $y1,float $x2,float $y2,string $rgb='0.82 0.84 0.88'): void {$this->cmd("{$rgb} RG 0.6 w {$x1} {$y1} m {$x2} {$y2} l S");}
    private function fill(float $x,float $y,float $w,float $h,string $rgb): void {$this->cmd("{$rgb} rg {$x} {$y} {$w} {$h} re f");}
    private function box(float $x,float $y,float $w,float $h): void {$this->cmd("0.82 0.84 0.88 RG 0.7 w {$x} {$y} {$w} {$h} re S");}
    private function newPage(): void { if($this->current)$this->pages[]=$this->current; $this->current=[]; $this->y=800; }
    private function ensure(float $needed=35): void {if($this->y<$needed){$this->footer();$this->newPage();}}
    private function footer(): void { $this->line(40,35,555,35,'0.88 0.89 0.92'); $this->text(40,20,'HOPE Health and Care - Document genere par le systeme ERP',7); }
    private function imageJpeg(float $x,float $y,float $w,float $h): void { $this->cmd("q {$w} 0 0 {$h} {$x} {$y} cm /Logo Do Q"); }
    private function header(string $kind,string $number): void {
        $this->fill(40,796,515,3,'0.44 0.16 0.90');
        $this->imageJpeg(40,748,72,67);
        $this->text(125,772,'HOPE HEALTH AND CARE',13,true,'0.10 0.07 0.25'); $this->text(125,754,'Sante Maternelle & ERP',8,false,'0.45 0.45 0.50');
        $this->text(360,772,$this->clean($kind),16,true,'0.10 0.07 0.25'); $this->text(360,754,$number,9,false,'0.45 0.45 0.50');
        $this->line(40,742,555,742,'0.82 0.84 0.88'); $this->y=715;
    }
    private function fieldBox(float $x,float $y,float $w,float $h,string $title,array $lines): void { $this->box($x,$y,$w,$h); $this->text($x+10,$y+$h-18,$title,8,true); $yy=$y+$h-34; foreach($lines as $line){$this->text($x+10,$yy,(string)$line,8);$yy-=13;if($yy<$y+8)break;} }
    private function money($v): string {return number_format((float)$v,0,',',' ').' XOF';}
    private function table(array $headers,array $rows,array $widths,float $x=40,float $rowH=23): void {
        $total=array_sum($widths); $this->fill($x,$this->y-$rowH,$total,$rowH,'0.94 0.93 0.98'); $cx=$x;
        foreach($headers as $i=>$h){$this->box($cx,$this->y-$rowH,$widths[$i],$rowH);$this->text($cx+5,$this->y-15,$h,7,true);$cx+=$widths[$i];}
        $this->y-=$rowH;
        foreach($rows as $row){$this->ensure($rowH+45);$cx=$x;$maxLines=1;$cells=[];foreach($headers as $i=>$h){$val=(string)($row[$i]??'');$max=28; $parts=str_split($this->clean($val),$max);$cells[]=$parts;$maxLines=max($maxLines,count($parts));}$h=$rowH*$maxLines;$cx=$x;foreach($cells as $i=>$parts){$this->box($cx,$this->y-$h,$widths[$i],$h);$yy=$this->y-15;foreach($parts as $part){$this->text($cx+5,$yy,$part,7);$yy-=11;}$cx+=$widths[$i];}$this->y-=$h;}
    }
    public function commercialDocument(CommercialDocument $doc): string {
        $doc->loadMissing(['customer','lines.product','order.warehouse']); $kind=['proforma'=>'FACTURE PROFORMA','invoice'=>'FACTURE','credit_note'=>'AVOIR'][$doc->type]??strtoupper($doc->type); $this->reset();$this->header($kind,$doc->document_number);
        $this->fieldBox(40,625,165,75,'INFORMATIONS CLIENT',['Client : '.($doc->customer?->name??'—'),'Email : '.($doc->customer?->email??'—'),'Telephone : '.($doc->customer?->phone??'—'),'Date : '.optional($doc->issue_date)->format('d/m/Y')]);
        $this->fieldBox(215,625,165,75,'ADRESSE DE FACTURATION',[$doc->customer?->address??'Adresse non renseignee',$doc->customer?->city??'Bamako','Mali']);
        $this->fieldBox(390,625,165,75,'ADRESSE DE LIVRAISON',[$doc->order?->warehouse?->name??'Entrepot HOPE',$doc->order?->warehouse?->address??'Adresse non renseignee','Mali']);
        $this->y=595; $rows=[];foreach($doc->lines as $l)$rows[]=[($l->product?->name??'Produit'),$l->product?->reference??'—',(string)$l->quantity,$this->money($l->unit_price),$this->money($l->line_total)];
        $this->table(['DESIGNATION','REFERENCE','QTE','PRIX UNITAIRE','PRIX HT'],$rows,[235,90,45,85,100]);
        $this->ensure(200);$this->text(45,$this->y-22,'Conditions / observations',8,true);$this->text(45,$this->y-38,$doc->notes?:'Aucune observation.',8);$this->y-=65;
        $this->fieldBox(40,max(70,$this->y-72),245,72,'MODE DE REGLEMENT',[$doc->payment_method? 'Mode : '.strtoupper(str_replace('_',' ',$doc->payment_method)):'Mode a convenir',$doc->status==='closed'?'Reglement : SOLDE':('Solde : '.$this->money($doc->balance_amount))]);
        $ty=max(70,$this->y-72);$this->box(320,$ty,235,72);$this->text(335,$ty+54,'TOTAL DOCUMENT',9,true);$this->text(435,$ty+54,$this->money($doc->total_amount),10,true);$this->text(335,$ty+35,'Sous-total',8);$this->text(435,$ty+35,$this->money($doc->subtotal),8);$this->text(335,$ty+18,'Paye / encaisse',8);$this->text(435,$ty+18,$this->money($doc->paid_amount),8);$this->text(335,$ty+2,'Solde',8,true);$this->text(435,$ty+2,$this->money($doc->balance_amount),9,true);$this->footer();return $this->build();
    }
    public function salesOrder(SalesOrder $o): string { $o->loadMissing(['customer','warehouse','lines.product']);$this->reset();$this->header('BON DE COMMANDE',$o->order_number);$this->fieldBox(40,625,245,75,'INFORMATIONS CLIENT',['Client : '.($o->customer?->name??'—'),'Email : '.($o->customer?->email??'—'),'Commande : '.$o->order_number,'Date : '.optional($o->order_date)->format('d/m/Y'),'Livraison prevue : '.optional($o->expected_date)->format('d/m/Y')]);$this->fieldBox(305,625,250,75,'PREPARATION',['Entrepot : '.($o->warehouse?->name??'—'),'Statut : '.$this->orderStatus($o->status),'Devise : '.$o->currency]);$this->y=595;$rows=[];foreach($o->lines as $l)$rows[]=[($l->product?->name??'Produit'),$l->product?->reference??'—',(string)$l->quantity,$this->money($l->unit_price),$this->money($l->line_total)];$this->table(['DESIGNATION','REFERENCE','QTE','PRIX UNITAIRE','TOTAL'],$rows,[235,90,45,85,100]);$this->ensure(180);$this->text(45,$this->y-20,'OBSERVATIONS',8,true);$this->text(45,$this->y-36,$o->notes?:'Aucune observation.');$ty=max(70,$this->y-100);$this->box(320,$ty,235,75);$this->text(335,$ty+55,'TOTAL COMMANDE',9,true);$this->text(440,$ty+55,$this->money($o->total_amount),10,true);$this->text(335,$ty+33,'Sous-total',8);$this->text(440,$ty+33,$this->money($o->subtotal),8);$this->text(335,$ty+13,'Remise',8);$this->text(440,$ty+13,$this->money($o->discount),8);$this->footer();return $this->build(); }
    public function purchaseOrder(PurchaseOrder $o): string { $o->loadMissing(['supplier','warehouse','lines.product']);$this->reset();$this->header('COMMANDE FOURNISSEUR',$o->order_number);$this->fieldBox(40,625,245,75,'FOURNISSEUR',['Fournisseur : '.($o->supplier?->name??'—'),'Email : '.($o->supplier?->email??'—'),'Date : '.optional($o->order_date)->format('d/m/Y'),'Reception prevue : '.optional($o->expected_date)->format('d/m/Y')]);$this->fieldBox(305,625,250,75,'RECEPTION',['Entrepot : '.($o->warehouse?->name??'—'),'Statut : '.$this->orderStatus($o->status),'Devise : '.$o->currency]);$this->y=595;$rows=[];foreach($o->lines as $l)$rows[]=[($l->product?->name??'Produit'),$l->product?->reference??'—',(string)$l->quantity,$this->money($l->unit_price),$this->money($l->line_total)];$this->table(['DESIGNATION','REFERENCE','QTE','PRIX UNITAIRE','TOTAL'],$rows,[235,90,45,85,100]);$ty=max(70,$this->y-80);$this->box(320,$ty,235,55);$this->text(335,$ty+35,'TOTAL ACHAT',9,true);$this->text(440,$ty+35,$this->money($o->total_amount),10,true);$this->text(335,$ty+15,'Remise',8);$this->text(440,$ty+15,$this->money($o->discount),8);$this->footer();return $this->build(); }
    public function paymentReceipt(Payment $p): string { $p->loadMissing(['document.customer','customer','recorder']);$this->reset();$this->header('RECU DE PAIEMENT','PAI-'.$p->id);$this->fieldBox(40,625,245,75,'CLIENT',['Client : '.($p->customer?->name??'—'),'Email : '.($p->customer?->email??'—'),'Facture : '.($p->document?->document_number??'—'),'Date : '.optional($p->paid_at)->format('d/m/Y H:i')]);$this->fieldBox(305,625,250,75,'ENCAISSEMENT',['Montant : '.$this->money($p->amount),'Mode : '.strtoupper(str_replace('_',' ',$p->method)),'Reference : '.($p->reference?:'—'),'Statut : '.$this->paymentStatus($p->status),'Agent : '.($p->recorder?->name??'—')]);$this->y=585;$this->box(40,450,515,105);$this->text(60,525,'RECU DE PAIEMENT',14,true);$this->text(60,495,'Nous confirmons la reception du paiement suivant :',9);$this->text(60,470,'Montant encaisse',9,true);$this->text(190,470,$this->money($p->amount),12,true);$this->text(60,452,'Reference',9);$this->text(190,452,$p->reference?:'Non renseignee',9);$this->text(40,410,'Note : '.($p->notes?:'Aucune note.'));$this->footer();return $this->build(); }
    public function report(string $type,array $data,$from,$to): string { $this->reset();$this->header('RAPPORT ERP - '.$this->reportLabel($type),'PERIODE '.$from->format('d/m/Y').' - '.$to->format('d/m/Y'));$this->text(40,710,'Synthese de gestion HOPE',12,true);$this->text(40,690,'Periode : '.$from->format('d/m/Y').' au '.$to->format('d/m/Y'),8);$this->y=655;foreach($data as $section=>$rows){$this->ensure(130);$this->fill(40,$this->y-24,515,24,'0.31 0.12 0.58');$this->text(50,$this->y-16,strtoupper($this->reportSection($section)),9,true,'1 1 1');$this->y-=24;if(!$rows){$this->text(50,$this->y-15,'Aucune donnee.',8);$this->y-=30;continue;} $headers=array_keys((array)$rows[0]);$widths=$this->reportWidths(count($headers));$tableRows=[];foreach($rows as $r){$vals=array_values((array)$r);$tableRows[]=array_map(fn($v)=>is_scalar($v)||$v===null?(string)$v:'',(array)$vals);} $this->table($headers,$tableRows,$widths);$this->y-=18;}$this->footer();return $this->build(); }
    private function reportWidths(int $n): array {$base=515/max(1,$n);return array_fill(0,$n,$base);}
    private function reset(): void {$this->pages=[];$this->current=[];$this->y=800;}
    private function build(): string {
        if($this->current)$this->pages[]=$this->current;
        $objects=[];$kids=[];
        $font1=3+count($this->pages)*2;$font2=$font1+1;$id=3;
        foreach($this->pages as $page){
            $pid=$id++;$cid=$id++;$kids[]=$pid;$content=implode("\n",$page);
            $objects[$pid]="<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 {$font1} 0 R /F2 {$font2} 0 R >> /XObject << /Logo __LOGO_ID__ 0 R >> >> /Contents {$cid} 0 R >>";
            $objects[$cid]="<< /Length ".strlen($content)." >>\nstream\n{$content}\nendstream";
        }
        $objects[1]='<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2]='<< /Type /Pages /Kids ['.implode(' ',array_map(fn($x)=>$x.' 0 R',$kids)).'] /Count '.count($this->pages).' >>';
        $objects[$font1]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[$font2]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $logoPath=base_path('public/hope-logo.jpg');$logoId=max(array_keys($objects))+1;$jpeg=is_file($logoPath)?file_get_contents($logoPath):false;$size=$jpeg!==false?@getimagesizefromstring($jpeg):false;
        foreach($objects as $k=>$v){$objects[$k]=str_replace('__LOGO_ID__',(string)$logoId,$v);}
        if($jpeg!==false && $size){$objects[$logoId]="<< /Type /XObject /Subtype /Image /Width {$size[0]} /Height {$size[1]} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ".strlen($jpeg)." >>\nstream\n".$jpeg."\nendstream";}
        ksort($objects);$pdf="%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";$off=[];
        foreach($objects as $oid=>$obj){$off[$oid]=strlen($pdf);$pdf.=$oid." 0 obj\n".$obj."\nendobj\n";}
        $max=max(array_keys($objects));$xref=strlen($pdf);$pdf.="xref\n0 ".($max+1)."\n0000000000 65535 f \n";for($i=1;$i<=$max;$i++)$pdf.=sprintf("%010d 00000 n \n",$off[$i]??0);$pdf.="trailer\n<< /Size ".($max+1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";return $pdf;
    }
    public function make(string $title,array $lines): string {$this->reset();$this->header($title,'');$this->y=710;foreach(array_merge(['HOPE HEALTH AND CARE',''],array_map(fn($x)=>(string)$x,$lines)) as $line){$this->ensure(45);$this->text(45,$this->y,$line,9);$this->y-=18;}$this->footer();return $this->build();}
    private function orderStatus($v):string{return ['draft'=>'Brouillon','validated'=>'Validee','processing'=>'En traitement','partially_delivered'=>'Partiellement livree','delivered'=>'Livree','cancelled'=>'Annulee','partially_received'=>'Partiellement recue','received'=>'Recue'][$v]??(string)$v;}
    private function paymentStatus($v):string{return ['recorded'=>'Enregistre','validated'=>'Valide','cancelled'=>'Annule'][$v]??(string)$v;}
    private function reportLabel($v):string{return ['sales'=>'Ventes','stock'=>'Etat du stock','movements'=>'Mouvements de stock','purchases'=>'Achats','payments'=>'Paiements'][$v]??$v;}
    private function reportSection($v):string{return ['summary'=>'Synthese','commandes'=>'Commandes clients','factures'=>'Factures','stock'=>'Etat du stock','mouvements'=>'Mouvements de stock','achats'=>'Achats fournisseurs','paiements'=>'Paiements'][$v]??$v;}
}
