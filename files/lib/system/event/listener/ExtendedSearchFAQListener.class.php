<?php
namespace wcf\system\event\listener;
use wcf\data\faq\FAQCache;
use wcf\data\faq\FAQList;
use wcf\data\search\extended\SearchExtendedGroup;
use wcf\data\search\extended\SearchExtendedItem;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;

/**
 * Extends the extended search of `cwalz.de` and prepares FAQ entries
 * as search results.
 *
 * @author	Dennis Kraffczyk
 * @copyright	2011-2018 KittMedia
 * @license	Free <https://kittmedia.com/licenses/#licenseFree>
 * @package	com.kittmedia.wcf.faqExtendedSearch
 * @category	Community Framework
 */
class ExtendedSearchFAQListener implements IParameterizedEventListener {
	/**
	 * Instance of the ExtendedSearchAction
	 * @var		\wcf\action\ExtendedSearchAction
	 */
	private $eventObj;
	
	/**
	 * @inheritDoc
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		if (!EXTENDED_SEARCH_FAQ_ENABLED || !FAQCache::getInstance()->hasAvailableFAQs()) {
			return;
		}
		
		$this->eventObj = $eventObj;
		$this->eventObj->data[] = $this->getFAQs();
	}
	
	/**
	 * Returns matched faq entries as result in a SearchExtendedGroup.
	 * @return SearchExtendedGroup
	 */
	private function getFAQs() {
		$accessibleCategoryIDs = \array_keys(FAQCache::getInstance()->getCategories());
		
		$faqList = new FAQList();
		$faqList->sqlLimit = EXTENDED_SEARCH_FAQ_COUNT;
		$faqList->sqlOrderBy = 'faq.views DESC';
		$faqList->getConditionBuilder()->add('faq.categoryID IN (?)', [ $accessibleCategoryIDs ]);
		$faqList->getConditionBuilder()->add('faq.isDisabled = ?', [ 0 ]);
		$faqList->readObjects();
		
		$items = [];
		foreach ($faqList->getObjects() as $faq) {
			$items[] = new SearchExtendedItem(
				$faq->getTitle(),
				LinkHandler::getInstance()->getLink(
					'FAQ',
					[
						'object' => $faq
					]
				),
				$faq->views,
				(EXTENDED_SEARCH_FAQ_ENABLE_PATH ? $faq->getCategory()->getTitle() : '')
			);
		}
		
		return new SearchExtendedGroup(WCF::getLanguage()->get('wcf.extendedSearch.group.faq'), $items, SearchExtendedGroup::POSITION_RIGHT);
	}
}
