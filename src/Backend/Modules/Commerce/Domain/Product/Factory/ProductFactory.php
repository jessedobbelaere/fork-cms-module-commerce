<?php

namespace Backend\Modules\Commerce\Domain\Product\Factory;

use Backend\Core\Language\Locale;
use Backend\Modules\Commerce\Domain\Category\Category;
use Backend\Modules\Commerce\Domain\Category\CategoryDataTransferObject;
use Backend\Modules\Commerce\Domain\Category\Image;
use Backend\Modules\Commerce\Domain\Product\Product;
use Backend\Modules\Commerce\Domain\Product\ProductDataTransferObject;
use Backend\Modules\Commerce\Domain\Product\ProductRepository;
use Backend\Modules\Commerce\Domain\ProductSpecial\ProductSpecial;
use Backend\Modules\Commerce\Domain\StockStatus\StockStatus;
use Backend\Modules\Commerce\Domain\StockStatus\StockStatusDataTransferObject;
use Backend\Modules\Commerce\Domain\Vat\Factory\VatFactory;
use Common\Doctrine\Entity\Meta;
use Common\Doctrine\ValueObject\SEOFollow;
use Common\Doctrine\ValueObject\SEOIndex;
use Common\Uri;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Money\Money;
use Symfony\Component\Form\Extension\Core\DataTransformer\MoneyToLocalizedStringTransformer;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Product>
 *
 * @method static Product[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static ProductRepository|RepositoryProxy repository()
 * @method Product|Proxy create(array|callable $attributes = [])
 *
 * @see: https://symfony.com/index.php/bundles/ZenstruckFoundryBundle/current/index.html
 * @codeCoverageIgnore
 */
final class ProductFactory extends ModelFactory
{
    /**
     * Our entities use private properties, no setters and a private constructor. Therefore, we have to use a DTO
     * object to instantiate our Entity.
     */
    protected function initialize(): self
    {
        return $this
            ->instantiateWith(function (array $attributes) {
                $dto = new ProductDataTransferObject(null, Locale::fromString('en'));
                $dto->meta = $attributes['meta'];
                $dto->category = $attributes['category'];
                $dto->brand = $attributes['brand'];
                $dto->vat = $attributes['vat'];
                $dto->stock_status = $attributes['stock_status'];
                $dto->hidden = $attributes['hidden'];
                $dto->type = $attributes['type'];
                $dto->title = $attributes['title'];
                $dto->weight = $attributes['weight'];
                $dto->price = $attributes['price'];
                $dto->stock = $attributes['stock'];
                $dto->sku = $attributes['sku'];
                $dto->ean13 = $attributes['ean13'];
                $dto->isbn = $attributes['isbn'];
                $dto->summary = $attributes['summary'];
                $dto->text = $attributes['text'];
                $dto->specials = $attributes['specials'];
                $dto->vat = $attributes['vat'];

                return Product::fromDataTransferObject($dto);
            });
    }

    protected function getDefaults(): array
    {
        $title = self::faker()->unique()->title;

        // Create a default StockStatus
        $stockStatus = new StockStatusDataTransferObject();
        $stockStatus->title = 'Available';
        $stockStatus->locale = Locale::fromString('en');

        // Create a random category
        $category = new CategoryDataTransferObject();
        $category->title = self::faker()->title;
        $category->locale = Locale::fromString('en');
        $category->sequence = 1;
        $category->extraId = 1;
        $category->image = Image::fromString('');
        $category->meta = new Meta($title, false, $title, false, $title, false, Uri::getUrl($title), false, null, false, null, SEOFollow::none(), SEOIndex::none());

        return [
            'meta' => new Meta($title, false, $title, false, $title, false, Uri::getUrl($title), false, null, false, null, SEOFollow::none(), SEOIndex::none()),
            'category' => Category::fromDataTransferObject($category),
            'brand' => null,
            'vat' => VatFactory::new(),
            'stock_status' => StockStatus::fromDataTransferObject($stockStatus),
            'hidden' => false,
            'type' => Product::TYPE_DEFAULT,
            'title' => $title,
            'weight' => self::faker()->randomFloat(null, 0.5, 200),
            'price' => Money::EUR((string) self::faker()->randomFloat(2, 1, 1000) * 100),
            'stock' => self::faker()->randomNumber(),
            'sku' => (string) self::faker()->randomNumber(),
            'ean13' => self::faker()->ean13,
            'isbn' => self::faker()->isbn13,
            'summary' => self::faker()->sentence(),
            'text' => self::faker()->paragraph(),
            'specials' => new ArrayCollection(),
        ];
    }

    protected static function getClass(): string
    {
        return Product::class;
    }

    public function withPrice(string $price): self
    {
        $moneyTransformer = new MoneyToLocalizedStringTransformer(2, true, null, 100);

        return $this->addState(['price' => Money::EUR($moneyTransformer->reverseTransform($price))]);
    }

    public function withVat(float $percentage, int $id = 1): self
    {
        $vat = VatFactory::new()->withPercentage($percentage)->create();
        $vat->forceSet('id', $id); // ID is a private property, but we need to set it to a value

        return $this->addState(['vat' => $vat]);
    }

    public function withNewSpecial(
        string $newPrice,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate = null
    ): self {
        $moneyTransformer = new MoneyToLocalizedStringTransformer(2, true, null, 100);

        $specials = new ArrayCollection();
        $special = new ProductSpecial();
        $special->setPrice(Money::EUR($moneyTransformer->reverseTransform($newPrice)));
        $special->setStartDate($startDate);
        $special->setEndDate($endDate);
        $specials->add($special);

        return $this->addState(['specials' => $specials]);
    }
}
