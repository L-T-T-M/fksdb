<?php

declare(strict_types=1);

namespace FKSDB\Models\ORM\Columns\Tables\StoredQuery\StoredQuery;

use FKSDB\Components\Forms\Controls\Autocomplete\AutocompleteSelectBox;
use FKSDB\Components\Forms\Controls\Autocomplete\StoredQueryTagTypeProvider;
use FKSDB\Models\ORM\Columns\ColumnFactory;
use FKSDB\Models\ORM\MetaDataFactory;
use FKSDB\Models\ORM\Models\StoredQuery\ModelStoredQueryTag;
use Fykosak\NetteORM\Model;
use FKSDB\Models\ORM\Models\StoredQuery\ModelStoredQuery;
use FKSDB\Models\ORM\Models\StoredQuery\ModelStoredQueryTagType;
use FKSDB\Models\ORM\Services\StoredQuery\ServiceStoredQueryTagType;
use Nette\Forms\Controls\BaseControl;
use Nette\Utils\Html;

class TagsColumnFactory extends ColumnFactory
{
    private ServiceStoredQueryTagType $serviceStoredQueryTagType;

    public function __construct(ServiceStoredQueryTagType $serviceStoredQueryTagType, MetaDataFactory $metaDataFactory)
    {
        parent::__construct($metaDataFactory);
        $this->serviceStoredQueryTagType = $serviceStoredQueryTagType;
    }

    /**
     * @param ModelStoredQuery $model
     */
    protected function createHtmlValue(Model $model): Html
    {
        $baseEl = Html::el('div');
        /** @var ModelStoredQueryTag $tagRow */
        foreach ($model->getTags() as $tagRow) {
            // TODO why ->stored_query_tag_type
            $tag = ModelStoredQueryTagType::createFromActiveRow($tagRow->tag_type);
            $baseEl->addHtml(
                Html::el('span')
                    ->addAttributes([
                        'class' => 'badge bg-color-' . $tag->color,
                        'title' => $tag->description,
                    ])
                    ->addText($tag->name)
            );
        }
        return $baseEl;
    }

    protected function createFormControl(...$args): BaseControl
    {
        $select = new AutocompleteSelectBox(true, $this->getTitle(), 'tags');
        $select->setDataProvider(new StoredQueryTagTypeProvider($this->serviceStoredQueryTagType));
        $select->setMultiSelect(true);
        return $select;
    }
}
