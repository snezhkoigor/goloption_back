<?php
/**
 * Created by PhpStorm.
 * User: igorsnezko
 * Date: 01.08.17
 * Time: 14:03
 */

namespace App\Http\Controllers\Api\V1\Product;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Support\Facades\DB;

class DownloadController extends Controller
{
    public function index($id, $trade_account)
    {
        $path = storage_path('app/public/history/forward_points/20170927/GFX-20170927-080151.txt');

        return response()->download($path);

//        if ($trade_account && $id) {
//            $product = DB::table('product_user')
//                ->select('products.path', 'product_user.download', 'users.country')
//                ->join('products', 'products.id', '=', 'product_user.product_id')
//                ->join('users', 'users.id', '=', 'product_user.user_id')
//                ->where([
//                    [ 'product_user.product_id', '=', $id ],
//                    [ 'product_user.trade_account', '=', $trade_account ]
//                ])
//                ->first();
//
//            if ($product) {
//                DB::table('product_user')
//                    ->where([
//                        [ 'product_id', '=', $id ],
//                        [ 'trade_account', '=', $trade_account ]
//                    ])
//                    ->update(['download' => $product->download + 1]);
//
//                // если этого не сделать файл будет читаться в память полностью!
//                if (ob_get_level()) {
//                    ob_end_clean();
//                }
//
//                $language = User::get_language($product->country);
//                $file = str_replace('{language}', $language, $product->path);
//
//
//                header("HTTP/1.1 200 OK");
//                header('Content-Description: File Transfer');
//                header('Content-Type: application/download',true,200);
//                header('Content-Disposition: attachment; filename='.basename($file));
//                header('Content-Transfer-Encoding: binary');
//                header('Expires: 0');
//                header('Cache-Control: must-revalidate');
//                header('Pragma: public');
//                header('Content-Length: ' . filesize($file));
//
//                readfile($file);
//                exit;
//            }
//
//            return response()->json([
//                'status' => false,
//                'message' => 'This user has not this product.',
//                'data' => null
//            ], 422);
//        }
//
//        return response()->json([
//            'status' => false,
//            'message' => 'No information.',
//            'data' => null
//        ], 422);
    }
}