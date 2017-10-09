<?php
/**
 * Created by PhpStorm.
 * User: igorsnezko
 * Date: 01.08.17
 * Time: 14:03
 */

namespace App\Http\Controllers\Api\V1\Product;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DownloadController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    public function index($id)
    {
        $user = JWTAuth::toUser(JWTAuth::getToken());

        if ($user) {
            $product = DB::table('product_user')
                ->select('path')
                ->where([
                    [ 'product_id', '=', $id ],
                    [ 'user_id', '=', $user['id'] ]
                ])
                ->first();

            if ($product) {
                return response()->download(public_path($product->path));
            }

            return response()->json([
                'status' => false,
                'message' => 'This user has not this product.',
                'data' => null
            ], 422);
        }

        return response()->json([
            'status' => false,
            'message' => 'No user.',
            'data' => null
        ], 422);
    }
}