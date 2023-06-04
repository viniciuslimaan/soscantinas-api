<?php

namespace App\Http\Controllers;

use App\Models\OpeningHours;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Show all users
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $return = User::all();
            $code = 200;
        } catch (\Exception $e) {
            $return = ['data' => ['msg' => 'Houve um erro ao listar todos os usuários!', 'error' => $e]];
            $code = 400;
        } finally {
            return response()->json($return, $code);
        }
    }

    /**
     * Add a new user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $userData = $request->all();

            User::create($userData);

            $return = ['data' => ['msg' => 'Usuário cadastrado com sucesso!']];
            $code = 200;
        } catch (\Exception $e) {
            $return = ['data' => ['msg' => 'Houve um erro ao criar um novo usuário!', 'error' => $e]];
            $code = 400;
        } finally {
            return response()->json($return, $code);
        }
    }

    /**
     * Show a specific user
     *
     * @param integer $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $return = User::with('payment_methods', 'opening_hours')->find($id)->toArray();
            $AddedProducts = Product::where('user_id', $id)->count();
            $OrderedProducts = Order::where('user_id', $id)->count();
            $withdrawnProducts = Order::where([['user_id', $id], ['status', 'withdrawn']])->count();
            $canceledProducts = Order::where([['user_id', $id], ['status', 'canceled']])->count();

            $summary = [
                'summary' => [
                    'added_products' => $AddedProducts,
                    'ordered_products' => $OrderedProducts,
                    'withtrawn_products' => $withdrawnProducts,
                    'canceled_products' => $canceledProducts,
                ]
            ];

            $return['summary'] = $summary['summary'];

            $code = 200;
        } catch (\Exception $e) {
            $return = ['data' => ['msg' => 'Houve um erro ao mostrar o usuário!', 'error' => $e]];
            $code = 400;
        } finally {
            return response()->json($return, $code);
        }
    }

    /**
     * Update a specific user
     *
     * @param Request $request
     * @param integer $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $userData = $request->all();
            $user = User::find($id);

            $user->update($userData);

            $return = ['data' => ['msg' => 'Usuário editado com sucesso!']];
            $code = 200;
        } catch (\Exception $e) {
            $return = ['data' => ['msg' => 'Houve um erro ao editar o usuário!', 'error' => $e]];
            $code = 400;
        } finally {
            return response()->json($return, $code);
        }
    }

    /**
     * Delete a specific user
     *
     * @param integer $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $user = User::find($id);

            OrderItem::whereIn('order_id', function ($query) use ($id) {
                $query->select('id')
                    ->from('orders')
                    ->where('user_id', $id);
            })->delete();
            Order::where('user_id', $id)->delete();
            Product::where('user_id', $id)->delete();
            PaymentMethod::where('user_id', $id)->delete();
            OpeningHours::where('user_id', $id)->delete();
            $user->delete();

            $return = ['data' => ['msg' => 'Usuário excluído com sucesso!']];
            $code = 200;
        } catch (\Exception $e) {
            $return = ['data' => ['msg' => 'Houve um erro ao excluir o usuário!', 'error' => $e]];
            $code = 400;
        } finally {
            return response()->json($return, $code);
        }
    }
}
