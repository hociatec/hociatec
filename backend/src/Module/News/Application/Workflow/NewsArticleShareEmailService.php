<?php

declare(strict_types=1);

namespace App\Module\News\Application\Workflow;

use App\Module\Marketing\Application\Notification\EmailTemplateRenderer;
use App\Module\News\Domain\Entity\NewsArticle;
use App\Shared\Application\Exception\MailDeliveryException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final readonly class NewsArticleShareEmailService
{
    public function __construct(
        private EmailTemplateRenderer $templates,
        private MailerInterface $mailer,
        private string $frontendUrl,
        private string $mailerFrom,
    ) {
    }

    public function send(NewsArticle $article, string $recipient): void
    {
        try {
            $this->deliver($article, $recipient);
        } catch (MailDeliveryException $exception) {
            throw $exception;
        } catch (\RuntimeException $exception) {
            throw MailDeliveryException::failed('news_article_share', $exception);
        }
    }

    private function deliver(NewsArticle $article, string $recipient): void
    {
        $frontendUrl = rtrim($this->frontendUrl, '/');
        $content = $this->templates->renderScenario('news_article_share', [
            'article_title' => $article->getTitle(),
            'article_excerpt' => $article->getExcerpt(),
            'article_url' => $frontendUrl.'/actualites/'.rawurlencode($article->getSlug()),
            'app_frontend_url' => $frontendUrl,
        ], [
            'subject' => 'À lire : {{article_title}}',
            'html' => '<p>Bonjour,</p><p>Voici une actualité Hociatec qui pourrait vous intéresser :</p><p><strong>{{article_title}}</strong></p><p>{{article_excerpt}}</p><p><a href="{{article_url}}">Lire l’actualité</a></p>',
            'text' => "Bonjour,\n\nVoici une actualité Hociatec qui pourrait vous intéresser :\n\n{{article_title}}\n{{article_excerpt}}\nLire l’actualité : {{article_url}}",
        ]);

        $email = (new Email())
            ->from(new Address($this->mailerFrom, 'Hociatec'))
            ->to(new Address($recipient))
            ->subject($content['subject'])
            ->html($content['html'])
            ->text($content['text']);

        try {
            $this->mailer->send($email);
        } catch (\RuntimeException $exception) {
            throw MailDeliveryException::failed('news_article_share', $exception);
        }
    }
}
