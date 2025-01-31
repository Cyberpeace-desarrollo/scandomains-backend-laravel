<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\FoundSuspiciousDomain;
use App\Models\Customer;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;



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
                }
            }
    
            if (!empty($domainsToInsert)) {
                FoundSuspiciousDomain::insert($domainsToInsert); // Insertar solo los nuevos
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

}
