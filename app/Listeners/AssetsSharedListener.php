<?php

namespace App\Listeners;

use App\Events\AssetsShared;
use App\Mail\SimpleEmail;
use Illuminate\Auth\Passwords\PasswordBroker;

class AssetsSharedListener extends AbstractListener
{
    public function viaQueue(): string
    {
        return self::LOW;
    }

    protected function handle2($event)
    {
        if (! ($event instanceof AssetsShared)) {
            throw new \Exception('Invalid event type!');
        }

        $sender = $event->owner;
        $sender->actAs(); // otherwise the tenant will not be properly set
        $from = 'cyberbuddy@cywise.io'; // config('towerify.freshdesk.from_email');
        $recipient = $event->recipient;
        $to = $recipient->email;

        $tags = implode(', ', $event->tags);
        $newRecipient = $event->newRecipient;

        if ($newRecipient) {
            $link = route('password.reset', [
                'token' => app(PasswordBroker::class)->createToken($recipient),
                'email' => $recipient->email,
                'reason' => 'Consultez les actifs partagés en créant un mot de passe',
                'action' => 'Créer mon mot de passe',
            ]);
        } else {
            $link = route('login');
        }

        $subject = "Cywise : Partage d'actifs";
        $title = 'Nouveaux actifs partagés avec vous';

        $body = ['<table cellspacing="0" cellpadding="0" style="margin: auto;"><tbody>'];
        $body[] = '<tr><td style="font-size: 28px; text-align: center;">Bonjour !</td></tr>';
        $body[] = '<tr><td style="font-size: 16px; line-height: 1.6;">';
        $body[] = '<p>'.htmlspecialchars($sender->name).' ('.htmlspecialchars($sender->email).') a partagé avec vous ses actifs avec les étiquettes ['.htmlspecialchars($tags).'].</p>';

        $body[] = '<p>Pour consulter les actifs partagés, connectez-vous à Cywise :</p>';
        $body[] = '</td></tr>';
        $body[] = $this->buildEmailCta($link);
        $body[] = '<tr><td style="font-size: 16px; line-height: 1.6;">';

        $body[] = '<p>Je reste à votre disposition pour toute question ou assistance supplémentaire. Merci encore pour votre confiance en Cywise !</p>';
        $body[] = '<p>Bien à vous,</p>';
        $body[] = '<p>CyberBuddy</p>';
        $body[] = '</td></tr>';
        $body[] = '</tbody></table>';

        SimpleEmail::sendEmail($subject, $title, implode("\n", $body), $to, $from);

    }

    private function buildEmailCta(string $link): string
    {
        return "
            <tr>
                <td align=\"center\" style=\"background-color: #fbca3e; padding: 10px 20px; border-radius: 5px;\">                    
                    <a href=\"{$link}\" target=\"_blank\" style=\"color: white; text-decoration: none; font-weight: bold;\">
                      Je me connecte à Cywise
                    </a>
                </td>
            </tr>
        ";
    }
}
