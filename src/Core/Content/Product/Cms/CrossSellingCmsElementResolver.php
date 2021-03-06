<?php declare(strict_types=1);

namespace Shopware\Core\Content\Product\Cms;

use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Content\Cms\SalesChannel\Struct\CrossSellingStruct;
use Shopware\Core\Content\Product\SalesChannel\CrossSelling\AbstractProductCrossSellingRoute;

/**
 * @internal (flag:FEATURE_NEXT_10078)
 */
class CrossSellingCmsElementResolver extends AbstractProductDetailCmsElementResolver
{
    /**
     * @var AbstractProductCrossSellingRoute
     */
    private $crossSellingLoader;

    public function __construct(
        AbstractProductCrossSellingRoute $crossSellingLoader
    ) {
        $this->crossSellingLoader = $crossSellingLoader;
    }

    public function getType(): string
    {
        return 'cross-selling';
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $config = $slot->getFieldConfig();
        $context = $resolverContext->getSalesChannelContext();
        $struct = new CrossSellingStruct();
        $slot->setData($struct);

        $productConfig = $config->get('product');

        if (!$productConfig || $productConfig->getValue() === null) {
            return;
        }

        $product = null;

        if ($productConfig->isMapped() && $resolverContext instanceof EntityResolverContext) {
            $product = $this->resolveEntityValue($resolverContext->getEntity(), $productConfig->getValue());
        }

        if ($productConfig->isStatic()) {
            $product = $this->getSlotProduct($slot, $result, $productConfig->getValue());
        }

        if ($product === null) {
            return;
        }

        $crossSellings = $this->crossSellingLoader->load($product->getId(), $context)->getResult();

        if ($crossSellings->count()) {
            $struct->setCrossSellings($crossSellings);
        }
    }
}
