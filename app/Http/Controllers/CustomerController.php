<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\DomainCustomer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;



class UnauthorizedUserException extends \Exception {}

class CustomerController extends Controller
{
   
    public function addCustomer(Request $request)
    {

        try {
            $aUser = Auth::user();
            if (!$aUser) {
                return response()->json([
                    'status' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

             // Validar los datos recibidos
        $request->validate([
            'name' => 'required|string|max:255',
            'domains' => 'required|array',
            'domains.*' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048', // Imagen opcional
        ]);
    
            // Verificar si el cliente ya existe
            $customer = Customer::where('name', $request->name)->first();
    
            if ($customer) {
                return response()->json([
                    'status' => false,
                    'message' => 'El cliente ya existe',
                    'data' => [
                        'customer' => $customer,
                    ]
                ], 200);
            }
    
            // Crear el nuevo cliente
            $customer = Customer::create([
                'name' => $request->name,
            ]);
    
            // Guardar la imagen si se envió
            if ($request->hasFile('image')) {
                $imagen = $request->file('image');
                $nombreImagen = Str::uuid() . '.' . $imagen->getClientOriginalExtension();
    
                $imagenServidor = Image::make($imagen);
                $imagenServidor->fit(1000, 1000);
                $imagenPath = public_path('uploads') . '/' . $nombreImagen;
    
                if (!file_exists(public_path('uploads'))) {
                    mkdir(public_path('uploads'), 0777, true);
                }
    
                $imagenServidor->save($imagenPath);
    
                // Guardar la URL en la BD
                $customer->photo_url = 'uploads/' . $nombreImagen;
                $customer->save();
            }
    
            // Insertar los dominios relacionados
            foreach ($request->domains as $domain) {
                DomainCustomer::create([
                    'domain' => $domain,
                    'customer_id' => $customer->id,
                ]);
            }
    
            return response()->json([
                'status' => true,
                'message' => 'Cliente y dominios agregados correctamente',
                'data' => [
                    'customer' => $customer,
                    'domains' => $request->domains,
                ]
            ], 201);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Ocurrió un error al agregar el cliente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    

    public function addDomainToCustomer(Request $request)
{
    try {
        // Validación de los datos de entrada
        $validated = $request->validate([
            'name' => 'required|string|exists:customers,name',
            'domains' => 'required|array',
            'domains.*' => 'required|string'
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
        $existingDomains = DomainCustomer::where('customer_id', $customer->id)
            ->pluck('domain')
            ->toArray(); // Obtener dominios ya asignados al cliente

        foreach ($validated['domains'] as $domain) {
            if (!in_array($domain, $existingDomains)) {
                $domainsToInsert[] = [
                    'customer_id' => $customer->id,
                    'domain' => $domain,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
        }

        if (!empty($domainsToInsert)) {
            DomainCustomer::insert($domainsToInsert); // Insertar solo dominios nuevos
        }

        return response()->json([
            'status' => true,
            'message' => 'Dominios agregados exitosamente al cliente',
            'data' => [
                'customer_name' => $customer->name,
                'added_domains' => array_column($domainsToInsert, 'domain'),
                'skipped_domains' => array_values(array_intersect($validated['domains'], $existingDomains))
            ]
        ], 201);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Error al agregar los dominios al cliente',
            'error' => $e->getMessage()
        ], 500);
    }
}



    public function viewCustomers()
    {
        try {
            $aUser = Auth::user();
            if (!$aUser) {
                throw new UnauthorizedUserException('Usuario no autenticado');
            }
            // Obtiene todos los clientes con sus dominios
            $customers = Customer::with('domainCustomers')->get();

            return response()->json([
                'status' => true,
                'message' => 'Lista de clientes obtenida exitosamente',
                'data' => $customers
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error al obtener la lista de clientes',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
