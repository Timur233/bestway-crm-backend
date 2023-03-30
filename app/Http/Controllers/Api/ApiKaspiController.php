<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;

class ApiKaspiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $client = new Client();
        $response = $client->request('GET', 'https://kaspi.kz/shop/api/v2/orders', [
            'headers' => [
                'Content-Type' => 'application/vnd.api+json',
                'X-Auth-Token' => 'v5fgjD5Y2v7++RytwB2RV0ndMqBbVgSpAaE/EytLwgw='
            ],
            'query' => [
                'page[number]' => '0',
                'page[size]' => '1000',
                'filter[orders][state]' => 'ARCHIVE',
                'filter[orders][creationDate][$ge]' => '1679881434000',
                'filter[orders][creationDate][$le]' => '1680054234000',
            ]
        ]);

        $body = $response->getBody();
        $content = $body->getContents();

        Log::error('str #56 ' . $content);

        return $content;
    }
}
