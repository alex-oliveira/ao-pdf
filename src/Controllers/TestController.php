<?php

namespace AOPDF\Controllers;

use AOPDF\AOPDF;
use App\Http\Controllers\Controller;

class TestController extends Controller
{

    public function test()
    {
        $client = [
            'client_name' => 'Alex Oliveira',
            'client_cpf' => '12345678900',
            'client_cnpj' => '14637972000104',
            'client_birth_at' => '01/01/1900',
            'client_tax' => 10.5,
            'client_age' => 100,
            'client_weight' => 100.00,
            'client_salary' => 10000.00,
            'client_created_at' => 1590445141,
            'client_skills' => [
                'PHP', 'JS'
            ],
            'client_address' => [
                'cep' => '77888999',
                'uf' => 'DF',
                'city' => 'Brasília',
                'neighborhood' => 'Samambaia Sul',
                'street' => 'QR 309 Conjunto 08',
                'number' => '02',
                'complement' => '',
            ],
        ];

        $data = [];

        $data[] = [
            'template' => 'https://github.com/alex-oliveira/ao-pdf/raw/master/example.pdf',
            'params' => $client
        ];

        $data[] = [
            'template' => 'https://github.com/alex-oliveira/ao-pdf/raw/master/example.pdf',
            'config' => [
                'all' => ['unaccented'],
                'select' => ['client_skills'],
                'upper' => ['client_name', 'client_address.city'],
                'unaccented' => ['client_address.city'],
                'decimal' => ['client_weight'],
                'rbl' => ['client_salary'],
                'date' => ['client_birth_at'],
                'cep' => ['client_address.cep'],
                'cpf' => ['client_cpf'],
                'cnpj' => ['client_cnpj'],
                'percent' => ['client_tax'],
                'fields' => [
                    'client_address.city' => ['unaccented']
                ],
            ],
            'params' => $client
        ];

        $data = AOPDF::encode($data);

        return redirect()->route('pdf.fill-by-get', ['data' => $data]);
    }

}
