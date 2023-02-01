<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Client;
use Cake\ORM\TableRegistry;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;

/**
 * Apis Controller
 *
 * @method \App\Model\Entity\Api[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ApisController extends AppController
{
    public function initialize(): void
    {
        $this->loadComponent('Webhook');
        $this->loadComponent('Flash');
    }

    public function index($id = null)
    {
        if ($this->request->is('post')) {
            $code = TableRegistry::getTableLocator()->get('CodeChats')->find('all')->where(['api_key' => $id])->first();
            if ($code){
                $server = TableRegistry::getTableLocator()->get('Servers')->find('all')->where(['id' => $code->server_id])->first();
                $jsonData = $this->request->getParsedBody();
                if ($jsonData['private']){
                    die();
                }

                if($jsonData['message_type'] == 'outgoing'){
                    $enviadoDe = $jsonData['sender']['name'];
                    $msg = $jsonData['content'];
                    $mensagemtipo = $jsonData['conversation']['meta']['sender']['email'];
                    $enviarPara = $jsonData['conversation']['meta']['sender']['phone_number'];

                    if ($mensagemtipo != null){
                        if (substr($mensagemtipo, -5) == '@g.us' ){
                            $enviarPara = $jsonData['conversation']['meta']['sender']['email'];
                        }
                    }

                    $arrayJson = $jsonData['conversation']['messages'][0]; //checar ISSO
                    if (array_key_exists('attachments', $arrayJson)){
                        $this->Webhook->mediamessage($server->url,
                            $server->api_geral,
                            $code->api_key,
                            $arrayJson['attachments'][0]['data_url'],
                            $msg,
                            $arrayJson['attachments'][0]['file_type'],
                            $enviarPara
                        );
                    }else{
                        $this->Webhook->message($server->url,$server->api_geral,$code->api_key,$msg,$enviarPara,$enviadoDe);
                    }
                }
            }
        }
    }

    public function download($id){

         $dados = base64_decode($id);
         $dados = explode('&&',$dados);

         $url = $dados['1'];
         $instancia = $dados['2'];
         $api = $dados['0'];
         $id = $dados['3'];

         $this->Webhook->_downloadmedia($url,$instancia,$id,$api);

    }
    public function tochatwoot()
    {
        if($this->request->is('post')) {
            $jsonData = $this->request->getParsedBody();

            if ($jsonData['event'] == 'messages.upsert'){
                $id = $jsonData['instance'];
                $mensagemEmgrupo = $jsonData['data']['key']['remoteJid'];
                 $code = TableRegistry::getTableLocator()->get('CodeChats')->find('all')->where(['api_key' => $id])->first();
                 $chatwoot = TableRegistry::getTableLocator()->get('Chatwoots')->find('all')->where(['code_chat_id' => $code->id])->first();
                 $server = TableRegistry::getTableLocator()->get('Servers')->find('all')->where(['id' => $code->server_id])->first();


/*                if (array_key_exists('extendedTextMessage',$arrayJson)){
                    if (array_key_exists('quotedMessage',$arrayJson['extendedTextMessage']['contextInfo'])){
                        $msg = "'".$arrayJson['extendedTextMessage']['contextInfo']['conversation']."'". "\n".$arrayJson['extendedTextMessage']['text'];
                    }

                    $this->Webhook->message($server->url,$server->api_geral,$code->api_key,$msg,$enviarPara,$enviadoDe);
                }*/


                 if (substr($mensagemEmgrupo, -5) == '@g.us'){
                     $email = $jsonData['data']['key']['remoteJid'];
                     $participante = $jsonData['data']['pushName'];
                     $numeroUser = $jsonData['data']['key']['participant'];
                     $inbox = $chatwoot->inbox;
                     $apiChat = $chatwoot->user_api;
                     $account = $chatwoot->account_id;

                     $contato = $this->Webhook->checkContact($account,$email,$apiChat);

                     if ($contato['meta']['count'] >= 1){


                         $searchConversations = $this->Webhook->searchconv($apiChat,$inbox,$account);
                         //   var_dump($searchConversations);
                         $searchConversations = json_decode($searchConversations);
                         $IDcontato = $contato['payload'][0]['id'];

                         foreach ($searchConversations->data->payload as $conversa){

                             if ($conversa->meta->sender->id == $IDcontato and $conversa->status != "resolved"){
                                 if (array_key_exists('conversation',$jsonData['data']['message']) || array_key_exists('extendedTextMessage', $jsonData['data']['message'])){
                                     if (array_key_exists('conversation',$jsonData['data']['message'])){
                                         $mensagem = '***'.$participante.'***👉: '.$jsonData['data']['message']['conversation'];
                                     }else{
                                         $mensagem = '***'.$participante.'***👉: '.$jsonData['data']['message']['extendedTextMessage']['text'];
                                         if (array_key_exists('contextInfo',$jsonData['data']['message']['extendedTextMessage'])){
                                          if (array_key_exists('quotedMessage',$jsonData['data']['message']['extendedTextMessage']['contextInfo'])){
                                              $respostaA = str_replace("@s.whatsapp.net","",$jsonData['data']['message']['extendedTextMessage']['contextInfo']['participant']);
                                           $mensagem ='***'.$participante.'*** em resposta a '.$respostaA.'👉: '."`".$jsonData['data']['message']['extendedTextMessage']['contextInfo']['quotedMessage']['conversation']."`\n".$jsonData['data']['message']['extendedTextMessage']['text'];
                                             }
                                         }
                                     }
                                     $this->Webhook->sendText($apiChat,$account,$conversa->id,$mensagem,$jsonData['data']['key']['fromMe']);
                                 }else{
                                     $media = $this->Webhook->sendMedia($account,$conversa->id,$jsonData,$apiChat,$server->api_geral,$server->url,$code->api_key,$participante);
                                 }
                                 die();
                             }
/*
Descomentar CASO queira que se ABRA uma nova CONVERSA;
  if ($conversa->meta->sender->id == $IDcontato and $conversa->status == "resolved"){
                                 $novaConversa = $this->Webhook->createConversation($account, $apiChat, $inbox, $IDcontato, $email);
                                 $IDConversa = $novaConversa['id'];
                                 if (array_key_exists('conversation',$jsonData['data']['message']) || array_key_exists('extendedTextMessage', $jsonData['data']['message'])){
                                     if (array_key_exists('conversation',$jsonData['data']['message'])){
                                         $mensagem = '***'.$participante.'***👉: '.$jsonData['data']['message']['conversation'];
                                     }else{
                                         $mensagem = '***'.$participante.'***👉: '.$jsonData['data']['message']['extendedTextMessage']['text'];
                                         if (array_key_exists('contextInfo',$jsonData['data']['message']['extendedTextMessage'])){
                                             if (array_key_exists('quotedMessage',$jsonData['data']['message']['extendedTextMessage']['contextInfo'])){
                                                 $respostaA = str_replace("@s.whatsapp.net","",$jsonData['data']['message']['extendedTextMessage']['contextInfo']['participant']);
                                                 $mensagem ='***'.$participante.'*** em resposta a '.$respostaA.'👉: '."`".$jsonData['data']['message']['extendedTextMessage']['contextInfo']['quotedMessage']['conversation']."`\n".$jsonData['data']['message']['extendedTextMessage']['text'];
                                             }
                                         }
                                     }
                                     $this->Webhook->sendText($apiChat,$account,$conversa->id,$mensagem,$jsonData['data']['key']['fromMe']);
                                 }else{
                                     $media = $this->Webhook->sendMedia($account,$IDConversa,$jsonData,$apiChat,$server->api_geral,$server->url,$code->api_key,$participante);
                                 }
                                 die();
                             }*/
                         }
                         die();
                     }else{
                         $createContactGroup = $this->Webhook->createGroupContact($server->api_geral,$server->url,$email, $code->api_key,$account,$apiChat);
                         $idGrupo =  $createContactGroup['payload']['contact']['id'];

                         $novaConversa = $this->Webhook->createConversation($account, $apiChat, $inbox, $idGrupo, $email);
                         $IDConversa = $novaConversa['id'];
                         $participante = $jsonData['data']['pushName'];
                         if (array_key_exists('conversation',$jsonData['data']['message'])){
                           //  $mensagem = $jsonData['data']['message']['conversation'];
                             $mensagem = '***'.$participante.'***👉: '.$jsonData['data']['message']['conversation'];
                            $msg = $this->Webhook->sendText($apiChat,$account,$IDConversa,$mensagem,$jsonData['data']['key']['fromMe']);
                         }else{
                             $media = $this->Webhook->sendMedia($account,$IDConversa,$jsonData,$apiChat,$server->api_geral,$server->url,$code->api_key,$participante);

                         }
                         die();
                     }
                 }else{
                     $numero = $jsonData['data']['key']['remoteJid'];
                     $numero = str_replace("@s.whatsapp.net",'',$numero);
                     $numero = '+'.$numero;
                     $nomeContato = $jsonData['data']['pushName'];
                     $inbox = $chatwoot->inbox;
                     $apiChat = $chatwoot->user_api;
                     $account = $chatwoot->account_id;
                     $contato = $this->Webhook->checkContact($account,$numero,$apiChat);

                     if($contato['meta']['count'] >= 1){
                         $searchConversations = $this->Webhook->searchconv($apiChat,$inbox,$account);
                      //   var_dump($searchConversations);
                         $searchConversations = json_decode($searchConversations);
                        $IDcontato = $contato['payload'][0]['id'];

                         foreach ($searchConversations->data->payload as $conversa){
                             if ($conversa->meta->sender->id == $IDcontato and $conversa->status != "resolved"){
                                 if (array_key_exists('conversation',$jsonData['data']['message']) || array_key_exists('extendedTextMessage', $jsonData['data']['message'])){
                                     $mensagem = $jsonData['data']['message']['conversation'];
                                     if (array_key_exists('extendedTextMessage',$jsonData['data']['message'])){
                                         $mensagem = $jsonData['data']['message']['extendedTextMessage']['text'];
                                     }
                                     $this->Webhook->sendText($apiChat,$account,$conversa->id,$mensagem,$jsonData['data']['key']['fromMe']);
                                 }else{
                                     $media = $this->Webhook->sendMedia($account,$conversa->id,$jsonData,$apiChat,$server->api_geral,$server->url,$code->api_key);
                                 }
                             die();
                             }
                             if ($conversa->meta->sender->id == $IDcontato and $conversa->status == "resolved"){
                                 $novaConversa = $this->Webhook->createConversation($account, $apiChat, $inbox, $IDcontato, $numero);
                                 $IDConversa = $novaConversa['id'];
                                 if (array_key_exists('conversation',$jsonData['data']['message']) || array_key_exists('extendedTextMessage', $jsonData['data']['message'])){
                                     $mensagem = $jsonData['data']['message']['conversation'];
                                     if (array_key_exists('extendedTextMessage',$jsonData['data']['message'])){
                                         $mensagem = $jsonData['data']['message']['extendedTextMessage']['text'];
                                     }
                                     $this->Webhook->sendText($apiChat,$account,$conversa->id,$mensagem,$jsonData['data']['key']['fromMe']);
                                 }else{
                                     $media = $this->Webhook->sendMedia($account,$IDConversa,$jsonData,$apiChat,$server->api_geral,$server->url,$code->api_key);
                                 }
                                 die();
                             }
                         }
                     }else{
                         $createContact = $this->Webhook->createContact($account,$numero,$apiChat,$nomeContato,$server->api_geral,$server->url,$code->api_key);
                         sleep(1);
                         $idContato =  $createContact['payload']['contact']['id'];
                         $novaConversa = $this->Webhook->createConversation($account, $apiChat, $inbox, $idContato, $numero);
                         $IDConversa = $novaConversa['id'];
                         if (array_key_exists('conversation',$jsonData['data']['message']) || array_key_exists('extendedTextMessage', $jsonData['data']['message'])){
                             $mensagem = $jsonData['data']['message']['conversation'];
                             if (array_key_exists('extendedTextMessage',$jsonData['data']['message'])){
                                 $mensagem = $jsonData['data']['message']['extendedTextMessage']['text'];
                             }
                             $this->Webhook->sendText($apiChat,$account,$IDConversa,$mensagem,$jsonData['data']['key']['fromMe']);
                         }else{
                             $media = $this->Webhook->sendMedia($account,$IDConversa,$jsonData,$apiChat,$server->api_geral,$server->url,$code->api_key);
                         }
                         die();
                     }
                 }
            }
        }
    }


}
