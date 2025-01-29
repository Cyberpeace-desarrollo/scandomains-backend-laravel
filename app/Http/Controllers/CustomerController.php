<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\DomainCustomer;
use Illuminate\Support\Facades\Auth;

class UnauthorizedUserException extends \Exception {}

class CustomerController extends Controller
{
    public function addCustomer(Request $request)
    {
        // Validar los datos recibidos
        $request->validate([
            'name' => 'required|string|max:255',
            'domains' => 'required|array',
            'domains.*' => 'required|string|max:255',
        ]);

        try {
            $aUser = Auth::user();
            if (!$aUser) {
                throw new UnauthorizedUserException('Usuario no autenticado');
            }

            // Crear el registro en la tabla 'customers'
            $customer = Customer::create([
                'name' => $request->name,
            ]);

            // Insertar los dominios relacionados en la tabla 'domain_customer'
            foreach ($request->domains as $domain) {
                DomainCustomer::create([
                    'domain' => $domain,
                    'customer_id' => $customer->id,
                ]);
            }

            // Retornar respuesta exitosa
            return response()->json([
                'status' => true,
                'message' => 'Cliente y dominios agregados correctamente',
                'data' => [
                    'customer' => $customer,
                    'domains' => $request->domains,
                ],
            ], 201);

        } catch (\Exception $e) {
            // Manejar errores
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
