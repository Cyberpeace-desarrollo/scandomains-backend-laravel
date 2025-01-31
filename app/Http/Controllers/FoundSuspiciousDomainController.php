<?php

namespace App\Http\Controllers;
use App\Models\FoundSuspiciousDomain;
use App\Models\Customer;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;


class InvalidUsersException extends \Exception {}
class InvalidCredentialsException extends \Exception {}
class UnauthorizedUserException extends \Exception {}

use Illuminate\Http\Request;

class FoundSuspiciousDomainController extends Controller
{
    public function addSuspiciousDomains(Request $request)
{
    try {
        // Validar los datos de entrada
        $validated = $request->validate([
            'name' => 'required|string|exists:customers,name',
            'suspicious_domains' => 'required|array',
            'suspicious_domains.*' => 'required|string'
        ]);

        // Buscar el cliente por su nombre
        $customer = Customer::where('name', $validated['name'])->first();

        if (!$customer) {
            return response()->json([
                'status' => false,
                'message' => 'Cliente no encontrado'
            ], 404);
        }

        $domainsToInsert = [];
        $existingDomains = FoundSuspiciousDomain::where('customer_id', $customer->id)
            ->pluck('suspicious_domain')
            ->toArray(); // Obtener dominios ya registrados para ese cliente

        foreach ($validated['suspicious_domains'] as $domain) {
            if (!in_array($domain, $existingDomains)) {
                $domainsToInsert[] = [
                    'customer_id' => $customer->id,
                    'suspicious_domain' => $domain,
                    'found_date' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                $newDomains[] = $domain;
            }
        }

        if (!empty($domainsToInsert)) {
            FoundSuspiciousDomain::insert($domainsToInsert); // Insertar solo los nuevos
            
            $mensaje = "**🛑 Se agrego un nuevo dominio sospechosos**\n";
            $mensaje .= "Para el cliente: **{$customer->name}**.\n\n";
            $mensaje .= "🔍 **Dominio registrado:**\n" . implode("\n", array_map(fn($d) => "- {$d}", $newDomains));
        
            $webhookUrl =  env('GENERAL_CHANNEL_URL');
        
            Http::withOptions([
                'verify' => false, // Desactiva la verificación SSL
            ])->post($webhookUrl, [
                'content' => $mensaje,
                'username' => env('NAME_BOT'),
                'avatar_url' => env('ICON_WARNING'),
            ]);
        
        }

        

        return response()->json([
            'status' => true,
            'message' => 'Dominios sospechosos agregados correctamente',
            'data' => [
                'customer_name' => $customer->name,
                'added_domains' => array_column($domainsToInsert, 'suspicious_domain'),
                'skipped_domains' => array_values(array_intersect($validated['suspicious_domains'], $existingDomains))
            ]
        ], 201);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Error al agregar los dominios sospechosos',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function viewSuspiciousDomains()
{
    try {
        // Obtener todos los registros de la tabla found_suspicious_domains con el cliente asociado
        $suspiciousDomains = FoundSuspiciousDomain::with('customer')->get();

        return response()->json([
            'status' => true,
            'message' => 'Lista de dominios sospechosos obtenida exitosamente',
            'data' => $suspiciousDomains
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Error al obtener la lista de dominios sospechosos',
            'error' => $e->getMessage()
        ], 500);
    }
}

}
