# AOPDF

This package is for those who want to create their own customized pdf service.
If you want to use the standard container service, use ao-pdf-docker: https://github.com/alex-oliveira/ao-pdf-docker



## PREREQUISITES

### Install "pdftk"

UBUNTU
````
$ sudo snap install pdftk
$ sudo ln -s /snap/bin/pdftk /bin/pdftk
````

https://linuxhint.com/install_pdftk_ubuntu/

https://askubuntu.com/questions/1028522/how-can-i-install-pdftk-in-ubuntu-18-04-and-later



## INSTALLATION

### 1) Install
````
$ composer require alex-oliveira/ao-pdf
````

### 2) Configure "config/app.php" file
````
'providers' => [
    /*
     * Package Service Providers...
     */
    AOPDF\AOPDFServiceProvider::class,
],
````



## ROUTE

O ServiceProvider do pacote disponibiliza quatro rotas para utilização dos recursos:

##### GET: /pdf/fill

* Rota para **criação e download** de documentos curtos. 

##### POST: /pdf/fill

* Rota para **criação** de documentos longos.

##### GET: /pdf/download

* Rota para **download** de documentos longos.

##### GET: /pdf/test

* Rota para **teste** de funcionalidade do pacote.



## UTILIZATION

### Basic
````
use AOPDF\AOPDF;

...

$document_part_1 = [
    'template' => 'https://github.com/alex-oliveira/ao-pdf/raw/master/example.pdf',
    'params' => [
        'client_name' => 'Alex Oliveira',
        'client_cpf' => '12345678900',
    ]
];

...

$data = AOPDF::encode([
  $document_part_1, $document_part_2, $document_part_3, $document_part_n...
]);
````

GET ( para requisições com **poucos dados** )
````
redirect()->to('http://{{__MY_HOST__}}/pdf/fill?data=' . $data);
````

POST (para requisições com **muito dados** )
````
$client = new GuzzleHttp\Client();
$response = $client->request('POST', 'http://{{__MY_HOST__}}/pdf/fill', [
    'form_params' => [
        'data' => $data
    ]
]);

$content = json_decode($response->getBody()->getContents());

redirect()->to('http://{{__MY_HOST__}}/pdf/download?file=' . $content->file_name);
````

PASSO A PASSO

1) Determinando a composição do documento.

    * Para compor um **documento** é necessário configurar uma ou mais partes para o mesmo.
  
    * Cada **parte** possui suas próprias configurações individuais.
  
    * O serviço processa cada **parte** separadamente e concatena todas no final para retornar
      **um único** arquivo PDF.
      
    * Não existem regras para a divisão do documento, você deve fazer isso apenas de for
      conveniente e/ou necessário para flexibilizar a utilização.

2) Determinando o **template** a ser utilizado para a criação de uma parte.

    * Cada **parte** deve ter um atributo chamado **template**, contendo uma URL
      para download de um arquivo PDF.

    * Na primeira utilização de uma URL, o pacote baixará o arquivo e guardará para
      as próximas utilizações. 
    
    * Para alterar o **template** a ser utilizado, basta alterar a URL fornecida na
      requisição.
      
    * O arquivo PDF indicado pela URL deve conter os campos que serão preenchidos. 

3) Determinando os parâmetros que serão utilizados para preencher os campos.

    * Cada **parte** deve ter um atributos chamado *params*, contendo uma lista de
      dados organizados como **chave** e **valor**.
      
    * A **chave** de cada atributo deve corresponder exatamente com o nome do campo
      no arquivo PDF que será utilizado como **template**.
      
    * **Chaves** não encontradas no **template** não geram erro, apenas são ignoradas.
    
    * É possível aninhar atributos e aplicar filtros, para se ter uma utilização mais
      rebuscada.
      
4) Preparando dados para processamento.

    * Após criar a lista com todas as configurações das partes do documento, é preciso
      codifica-lo para envio.
      
    * Utilize a função **AOPDF::encode($data)** para codificar corretamente os dados.
      
    * Uma vez com os dados codificados, você pode enviá-los para a
      rota **http://{{__MY_HOST__}}/pdf/fill**, por requisição **GET** ou **POST**.
      
    * Utilize **GET** para documentos que utilizam poucos dados e **POST** para
      documentos com muitos dados.
      
    * Se deseja simplificar a gestão do tipo de requisição, utilize apenas **POST**.

5) Sobre requisições **GET**.

    * Essa é a forma mais fácil de utilizar o serviço.
    
    * O dados são enviados via URL, por uma parâmetro chamado **data** na **queryString** e
      o download do arquivo PDF é iniciado como resposta a requisição.
      
    * O inconveniente deste método é que existe um limite para o envio de dados via URL.
      Se muitos dados são necessários, você será obrigado a usar **POST**.

6) Sobre requisições **POST**.

    * Essa forma adicionar mais passos ao fluxo de trabalho, mas permite o envio de muitos dados
      para a construção do documento.
    
    * Será preciso enviar os dados em um campo **data** no corpo da requisição, mas a resposta
      não será um arquivo PDF e sim um **nome de um arquivo temporário no servidor**.
      
    * Para obter o arquivo PDF será necessário fazer uma nova requisição, do tipo **GET**,
      para a rota **http://{{__MY_HOST__}}/pdf/download**, informando o **nome do arquivo temporário**
      em um campo chamado **file**, na queryString.
      
    * Fazendo isso o arquivo será processado e o download será iniciado.
    
7) Sistema de cache

    * O pacote tem um sistema de cache de arquivos. O arquivo PDF final de cada requisição
      é preservado por 6 horas, se a requisição se repetir durante esse período o arquivo do cache
      será retornado.
      
    * Os arquivos armazenados são identificados pelo hash md5 da requisição, sendo assim qualquer
      alteração em uma requisição muda o hash e assim é tratada como uma nova requisição.



### Using formatteres
````
````

### Available formatteres
````
````
