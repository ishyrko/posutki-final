<?php

declare(strict_types=1);

namespace App\Presentation\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateArticleRequest
{
    #[Assert\NotBlank(message: 'Укажите заголовок')]
    #[Assert\Length(
        min: 10,
        max: 200,
        minMessage: 'Заголовок не короче {{ limit }} символов',
        maxMessage: 'Заголовок не длиннее {{ limit }} символов'
    )]
    public string $title;

    #[Assert\NotBlank(message: 'Укажите текст статьи')]
    #[Assert\Length(
        min: 100,
        max: 50000,
        minMessage: 'Текст не короче {{ limit }} символов',
        maxMessage: 'Текст не длиннее {{ limit }} символов'
    )]
    public string $content;

    #[Assert\NotBlank(message: 'Укажите краткое описание')]
    #[Assert\Length(
        min: 50,
        max: 500,
        minMessage: 'Краткое описание не короче {{ limit }} символов',
        maxMessage: 'Краткое описание не длиннее {{ limit }} символов'
    )]
    #[Assert\Length(max: 2048)]
    public string $excerpt;

    #[Assert\Length(max: 255)]
    #[Assert\Regex(
        pattern: '#^/uploads/.+#',
        message: 'Обложка должна быть URL загруженного файла, начинающимся с /uploads/'
    )]
    public ?string $coverImage = null;

    public ?int $categoryId = null;

    #[Assert\Type('array')]
    public array $tags = [];
}
