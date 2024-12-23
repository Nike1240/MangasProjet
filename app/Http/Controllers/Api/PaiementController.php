<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FedaPayTransaction;
use FedaPay\FedaPay;
use FedaPay\Transaction;
use App\Models\User;
use App\Models\Transactions;

class PaiementController extends Controller
{
    public function __construct()
    {
        FedaPay::setApiKey(config('services.fedapay.api_key'));
        FedaPay::setEnvironment(config('services.fedapay.environment'));
    }

    public function processPayment(Request $request)
    {
        $request->validate([
            'description' => 'required|string',
            'amount' => 'required|integer|min:1',
            'firstname' => 'required|string',
            'lastname' => 'required|string',
        ]);

        try {
            $firstname = $request->input('firstname');
            $lastname = $request->input('lastname');
            
            // Créer d'abord l'enregistrement dans notre base de données
            $localTransaction = \App\Models\Transactions::create([
                'description' => $request->input('description'),
                'amount' => $request->input('amount'),
                'firstname' => $firstname,
                'lastname' => $lastname,
                'currency' => 'XOF',
                'status' => 'pending'
            ]);

            // Création de la transaction FedaPay
            $fedaPayTransaction = \FedaPay\Transaction::create([
                'description' => $request->input('description'),
                'amount' => $request->input('amount'),
                'currency' => ['iso' => 'XOF'],
                'callback_url' => route('callback'),
                'customer' => [
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                ],
            ]);


            // Mettre à jour l'ID FedaPay dans notre base
            $localTransaction->fedapay_id = $fedaPayTransaction->id;
            $localTransaction->save();

            // Génération du token et du lien de paiement
            $token = $fedaPayTransaction->generateToken();

            return response()->json([
                'status' => 'success',
                'message' => 'Transaction created successfully',
                'payment_url' => $token->url,
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur de paiement FedaPay', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function callback(Request $request)
    {
        try {
            $transactionId = $request->input('id');
            $fedaPayTransaction = \FedaPay\Transaction::retrieve($transactionId);

            // Retrouver notre transaction locale
            $localTransaction = \App\Models\Transactions::where('fedapay_id', $transactionId)->first();

            if (!$localTransaction) {
                throw new \Exception('Transaction locale non trouvée');
            }

            // Mise à jour du statut dans notre base de données
            if ($fedaPayTransaction->status === 'approved') {
                $localTransaction->status = 'completed';
                $localTransaction->save();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Payment was successful',
                ]);
            }

            $localTransaction->status = 'failed';
            $localTransaction->save();

            return response()->json([
                'status' => 'error',
                'message' => 'Payment failed',
            ], 400);

        } catch (\Exception $e) {
            \Log::error('Erreur callback FedaPay', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}



// class PaiementController extends Controller
    //  {
    //     public function __construct()
    //     {
    //         // Configurez FedaPay avec votre clé API et environnement
    //         FedaPay::setApiKey(config('services.fedapay.api_key'));
    //         FedaPay::setEnvironment(config('services.fedapay.environment'));

    //     }


    //     public function processPayment(Request $request)
    //     {

    //         $request->validate([
    //             'description' => 'required|string',
    //             'amount' => 'required|integer|min:1', // FedaPay exige un montant entier
    //             'firstname' => 'required|string', // Assurez-vous que ces champs sont obligatoires
    //             'lastname' => 'required|string',
    //             //'user_id' => [
    //                 //     'required',
    //                 //     function ($attribute, $value, $fail) {
    //                 //         $existsInArtists = \DB::table('artists')->where('user_id', $value)->exists();
    //                 //         $existsInClients = \DB::table('clients')->where('user_id', $value)->exists();
                
    //                 //         if (!$existsInArtists && !$existsInClients) {
    //                 //             $fail('The selected user ID is invalid. It must exist in either the artists or clients table.');
    //                 //         }
    //                 //     },
    //             // ],
    //         ]);
            

    //         try {
                
    //              // Récupérez les données du formulaire
    //                 $firstname = $request->input('firstname');
    //                 $lastname = $request->input('lastname');

    //                 \Log::info('Prénom et nom récupérés :', [
    //                     'firstname' => $firstname,
    //                     'lastname' => $lastname,
    //                 ]);
            
    //             // Création de la transaction
    //             $transaction = Transaction::create([
    //                 'description' => $request->input('description'),
    //                 'amount' => $request->input('amount'),
    //                 'currency' => ['iso' => 'XOF'],
    //                 'callback_url' => route('callback'), // URL de callback
    //                 'customer' => [
    //                     'firstname' => $firstname,
    //                     'lastname' => $lastname,
    //                 ],
    //             ]);


    //             // Génération du token et du lien de paiement
    //             $token = $transaction->generateToken();

    //             return response()->json([
    //                 'status' => 'success',
    //                 'message' => 'Transaction created successfully',
    //                 'payment_url' => $token->url, 
    //             ]);
    //         } catch (\Exception $e) {
            
    //             \Log::error('Erreur de paiement FedaPay', ['error' => $e->getMessage()]);
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => $e->getMessage(),
    //             ], 500);

    //         }      
    //     }

    //         public function callback(Request $request)
    //     {
    //         try {
    //             // Récupérer l'ID de la transaction depuis les paramètres de la requête
    //             $transactionId = $request->input('id');
    //             $transaction = Transaction::retrieve($transactionId);

    //             // Vérifiez le statut de la transaction
    //             if ($transaction->status === 'approved') {
    //                 // Mettez à jour votre base de données ou traitez l'action post-paiement
    //                 return response()->json([
    //                     'status' => 'success',
    //                     'message' => 'Payment was successful',
    //                 ]);
    //             }

    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'Payment failed',
    //             ], 400);
    //         } catch (\Exception $e) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => $e->getMessage(),
    //             ], 500);
    //         }
    //     }

//  }


