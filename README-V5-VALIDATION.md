# HOPE V5 — validation fonctionnelle ERP

## Parcours de recette

### 1. Commande client
1. Créer une commande brouillon.
2. Modifier la commande.
3. Passer `Brouillon` -> `Validée` -> `En traitement`.
4. Créer un bon de livraison avec une quantité partielle ou totale.
5. Vérifier que le stock global et le stock entrepôt diminuent.
6. Vérifier le PDF, le téléchargement et l'impression.
7. Envoyer le PDF par email après configuration SMTP.
8. Créer une facture depuis la commande si nécessaire.

Une commande ne peut pas être passée manuellement à `Livrée` : ce statut est produit par la livraison réelle.

### 2. Proforma -> facture
1. Créer une proforma.
2. `Brouillon` -> `Envoyée` -> `Acceptée`.
3. Cliquer `Créer facture`.
4. Vérifier que la proforma ne peut pas être convertie deux fois.
5. Ouvrir la facture, prévisualiser son PDF, télécharger et imprimer.
6. Envoyer la facture par email avec le PDF en pièce jointe.

### 3. Paiement
1. Une facture doit être `Acceptée` avant encaissement.
2. Enregistrer un paiement partiel.
3. Vérifier `Payé`, `Solde` et statut `Partiellement payée`.
4. Enregistrer un second paiement.
5. Vérifier le statut `Clôturée` lorsque le solde atteint zéro.
6. Annuler un paiement et vérifier que le solde de la facture est recalculé.
7. Prévisualiser/télécharger/imprimer le reçu PDF.
8. Envoyer le reçu par email.

Le paiement est manuel/externe : HOPE ne débite aucun compte Orange Money, Moov Money, banque ou PayPal.

### 4. Commande fournisseur
1. Créer/modifier une commande brouillon.
2. Valider.
3. Réceptionner une quantité partielle ou totale.
4. Vérifier les stocks et les mouvements.
5. Ouvrir le PDF, imprimer, télécharger et envoyer par email.

### 5. Rapports
Tester :
- Ventes
- État du stock
- Mouvements de stock
- Achats fournisseurs
- Paiements

Pour chaque rapport : générer, voir PDF, imprimer, télécharger et envoyer par email.
Les rapports destinés à la direction utilisent les noms des clients, produits, entrepôts et agents plutôt que les IDs techniques de base de données.

## SMTP

Le seul élément qui dépend d'un service externe est l'envoi réel des emails. Configurer `MAIL_MAILER=smtp` et les paramètres SMTP dans `.env`.
