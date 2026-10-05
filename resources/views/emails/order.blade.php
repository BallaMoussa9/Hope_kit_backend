<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">

    <title>Commande HOPE</title>
</head>

<body style="font-family: Arial, sans-serif; background:#f5f7fa; 
padding:30px;">

    <div style="
        max-width:650px;
        margin:auto;
        background:white;
        padding:30px;
        border-radius:10px;
    ">

        <h2 style="margin-top:0;">
            HOPE Health and Care
        </h2>

        <p>
            Bonjour,
        </p>

        <p>
            Nous vous informons que votre commande
            <strong>
                {{ $order->order_number ?? '#' . $order->id }}
            </strong>
            a été enregistrée dans notre système.
        </p>

        @if($order->customer)
            <p>
                <strong>Client :</strong>
                {{ $order->customer->name }}
            </p>
        @endif

        <p>
            Vous trouverez les informations relatives à votre commande
            dans votre espace HOPE.
        </p>

        <p>
            Merci pour votre confiance.
        </p>

        <hr>

        <p style="font-size:12px;color:#777;">
            HOPE Health and Care<br>
            Cet email a été envoyé automatiquement.
        </p>

    </div>

</body>
</html>
