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
    public function index($id, $trade_account)
    {
        $user = JWTAuth::toUser(JWTAuth::getToken());

        if ($user) {
            $product = DB::table('product_user')
                ->select('products.path')
                ->join('products', 'products.id', '=', 'product_user.product_id')
                ->where([
                    [ 'product_user.product_id', '=', $id ],
                    [ 'product_user.trade_account', '=', $trade_account ]
                ])
                ->first();

            if ($product) {
                // если этого не сделать файл будет читаться в память полностью!
                if (ob_get_level()) {
                    ob_end_clean();
                }
                // заставляем браузер показать окно сохранения файла
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename=' . basename($product->path));
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                // читаем файл и отправляем его пользователю
                readfile($product->path);
                exit;
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