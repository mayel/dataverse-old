<?php

use Mpociot\BotMan\BotManFactory;
use Mpociot\BotMan\BotMan;
use Mpociot\BotMan\Middleware\ApiAi;

function bot_init()
{
    global $bv;

    error_log("botman init");

    try {
        header('Content-Type: application/json');

        // create an instance
        $botman = BotManFactory::create($bv->config->bot);

        $botman->verifyServices($bv->config->bot_verify);

        error_log("botman ready");


        // give the bot something to listen for.
        $botman->hears('hello', function (BotMan $bot) {
            bot_start($bot);
        });
        $botman->hears('start', function (BotMan $bot) {
            bot_start($bot);
        });
        $botman->hears('hi', function (BotMan $bot) {
            bot_start($bot);
        });



        $botman->fallback(function ($bot) {
            error_log("botman fallback");

            $bot->reply("Sorry, I did not understand. Please say 'hi' to get started.");
        });

        // start listening
        $botman->listen();
    } catch (UnexpectedValueException $e) {
        error_log("botman ERROR UnexpectedValueException - ".$e);
    } catch (Exception $e) {
        error_log("botman ERROR - ".$e);
    }


    error_log("botman done");
}


function bot_start($bot)
{
    global $bv;
    error_log("botman: start");

    $user = $bot->getUser();
    if ($user) {
        $bot->reply('Hello '.$user->getFirstName().' '.$user->getLastName());
        $uid = $user->getId();
        $uname = $user->getUsername();
        if ($uname) {
            $bot->reply('Your username is: '.$uname);
        }
        if ($uid) {
            $bot->reply('Your ID is: '.$uid);
        }
    } else {
        $bot->reply('Hello yourself.');
    }

    //	$bot->reply(ButtonTemplate::create('Do you want to know more about Dataverse?')
//		->addButton(ElementButton::create('Visit the website')->url($bv->config->home_url))
//		->addButton(ElementButton::create('Tell me more')->type('postback')->payload('tellmemore'))
//	);
}


function bot_setup_telegram()
{
    global $bv;

    return "https://api.telegram.org/bot".$bv->config->bot['telegram_token']."/setwebhook?url=".$bv->config->bot_url;
}
