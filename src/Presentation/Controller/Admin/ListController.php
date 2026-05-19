<?php

declare(strict_types=1);

namespace Phpro\SuluTranslationsBundle\Presentation\Controller\Admin;

use Phpro\SuluTranslationsBundle\Domain\Query\FetchTranslations;
use Phpro\SuluTranslationsBundle\Domain\Query\SearchCriteria;
use Sulu\Component\Rest\ListBuilder\ListRestHelperInterface;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

use function Psl\Type\int;

#[Route(path: '/translations', name: 'phpro.translations_list', options: ['expose' => true], methods: ['GET'])]
final class ListController extends AbstractSecuredTranslationsController implements SecuredControllerInterface
{
    public const string RESOURCE_KEY = 'phpro_translations';

    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ListRestHelperInterface $listRestHelper,
        private readonly FetchTranslations $fetchTranslations,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $limit = int()->coerce($this->listRestHelper->getLimit());
        $filter = $this->listRestHelper->getFilter();

        $translationsResult = ($this->fetchTranslations)(
            new SearchCriteria(
                (string) $this->listRestHelper->getSearchPattern(),
                [
                    'locale' => $filter['locale']['eq'] ?? null,
                    'domain' => $filter['domain']['eq'] ?? null,
                    'translationKey' => $filter['translationKey']['eq'] ?? null,
                ],
                $this->listRestHelper->getSortColumn(),
                $this->listRestHelper->getSortOrder(),
                (int) $this->listRestHelper->getOffset(),
                $limit
            )
        );

        $listRepresentation = new PaginatedRepresentation(
            $translationsResult->translationCollection(),
            self::RESOURCE_KEY,
            int()->coerce($this->listRestHelper->getPage()),
            $limit,
            $translationsResult->totalCount(),
        );

        return new JsonResponse(
            $this->serializer->serialize($listRepresentation->toArray(), 'json'),
            json: true
        );
    }
}
