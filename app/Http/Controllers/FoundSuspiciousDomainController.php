<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\FoundSuspiciousDomain;
use App\Models\Customer;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Http;



class InvalidUsersException extends \Exception {}
class InvalidCredentialsException extends \Exception {}
class UnauthorizedUserException extends \Exception {}



class FoundSuspiciousDomainController extends Controller
{
    public function addSuspiciousDomains(Request $request)
    {
        try {
            // Obtener el usuario autenticado
            $user = Auth::user();
    
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }
    
            // Validar los datos de entrada
            $validated = $request->validate([
                'name' => 'required|string|exists:customers,name',
                'suspicious_domains' => 'required|array',
                'suspicious_domains.*' => 'required|string',
                'image' => 'required|image|mimes:jpeg,png,jpg|max:2048', // Validación de la imagen
            ]);
    
            // Buscar el cliente por su nombre
            $customer = Customer::where('name', $validated['name'])->first();
    
            if (!$customer) {
                return response()->json([
                    'status' => false,
                    'message' => 'Cliente no encontrado'
                ], 404);
            }
    
            // Procesar la imagen
            $imagen = $request->file('image');
            $nombreImagen = Str::uuid() . '.' . $imagen->getClientOriginalExtension();
    
            $imagenServidor = Image::make($imagen);
            $imagenServidor->fit(1000, 1000);
            $imagenPath = public_path('uploads') . '/' . $nombreImagen;
    
            if (!file_exists(public_path('uploads'))) {
                mkdir(public_path('uploads'), 0777, true);
            }
    
            $imagenServidor->save($imagenPath);
    
            $domainsToInsert = [];
            $existingDomains = FoundSuspiciousDomain::where('customer_id', $customer->id)
                ->pluck('suspicious_domain')
                ->toArray(); // Obtener dominios ya registrados para ese cliente
    
            $newDomains = [];
            $fechaRegistro = now()->format('Y-m-d H:i:s');

            foreach ($validated['suspicious_domains'] as $domain) {
                if (!in_array($domain, $existingDomains)) {
                    $domainsToInsert[] = [
                        'customer_id' => $customer->id,
                        'suspicious_domain' => $domain,
                        'photo_url' => $nombreImagen, // Guardar el nombre de la imagen
                        'found_date' => now(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                    $newDomains[] = $domain;
                }
            }
    
            if (!empty($domainsToInsert)) {
                FoundSuspiciousDomain::insert($domainsToInsert);

                $mensaje = "**🛑 Se agregó un nuevo dominio sospechoso**\n";
                $mensaje .= "Para el cliente: **{$customer->name}**.\n";
                $mensaje .= "📅 Fecha de registro: **{$fechaRegistro}**\n\n";
                $mensaje .= "🔍 **Dominio registrado:**\n" . implode("\n", array_map(fn($d) => "- {$d}", $newDomains)) . "\n\n";
                $mensaje .= "**Alerta de Dominio Sospechoso**\n";
                $mensaje .= "Se detectó un nuevo dominio sospechoso.";
                
                $webhookUrl = env('GENERAL_CHANNEL_URL');

                Http::withOptions([
                    'verify' => false, 
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
                    'user' => $user->name, // Devolver el usuario que agregó los dominios
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

public function changeFlagSuspicious(Request $request)
{
    try {
        // Validar los datos de entrada
        $validated = $request->validate([
            'suspicious_domain' => 'required|string|exists:found_suspicious_domains,suspicious_domain',
            'flag' => 'required|boolean'
        ]);

        // Buscar el dominio en la base de datos
        $domain = FoundSuspiciousDomain::where('suspicious_domain', $validated['suspicious_domain'])->first();

        if (!$domain) {
            return response()->json([
                'status' => false,
                'message' => 'Dominio sospechoso no encontrado'
            ], 404);
        }

        // Actualizar el campo "flag"
        $domain->flag = $validated['flag'];
        $domain->save();

        return response()->json([
            'status' => true,
            'message' => 'El campo flag se ha actualizado correctamente',
            'suspicious_domain' => $domain->suspicious_domain,
            'flag_value' => $validated['flag']
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Error al actualizar el flag',
            'error' => $e->getMessage()
        ], 500);
    }
}



}
