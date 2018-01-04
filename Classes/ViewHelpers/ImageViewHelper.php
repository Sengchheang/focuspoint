<?php
namespace HDNET\Focuspoint\ViewHelpers;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */


use HDNET\Autoloader\Exception;
use HDNET\Focuspoint\Service\FocusCropService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ImageViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\ImageViewHelper
{
    /**
     * Initialize arguments.
     */
    public function initializeArguments()
    {
	    $this->registerArgument('ratio', 'string', 'Ratio of the image', false, '1:1');
	    $this->registerArgument('realCrop', 'boolean', 'Crop the image in real', false, true);
	    $this->registerArgument('additionalClassDiv', 'string', 'Additional class for focus point div', false, '');
    }

    /**
     * Resizes a given image (if required) and renders the respective img tag
     *
     * @see https://docs.typo3.org/typo3cms/TyposcriptReference/ContentObjects/Image/
     *
     * @throws \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
     * @return string Rendered tag
     */
    public function render()
    {
        if ((is_null($this->arguments['src']) && is_null($this->arguments['image'])) || (!is_null($this->arguments['src']) && !is_null($this->arguments['image']))) {
            throw new \TYPO3\CMS\Fluid\Core\ViewHelper\Exception('You must either specify a string src or a File object.', 1382284106);
        }
        $src = $this->arguments['src'];
        $image = $this->arguments['image'];
        $treatIdAsReference = $this->arguments['treatIdAsReference'];

	    /** @var FocusCropService $service */
	    $service = GeneralUtility::makeInstance(FocusCropService::class);
	    $internalImage = null;
	    try {
		    $internalImage = $service->getViewHelperImage($this->arguments['src'], $this->arguments['image'], $this->arguments['treatIdAsReference']);
		    if ($this->arguments['realCrop'] && $internalImage) {
			    $src = $service->getCroppedImageSrcByFile($internalImage, $this->arguments['ratio']);
			    $treatIdAsReference = false;
			    $image = null;
		    }
	    } catch (\Exception $ex) {
		    $this->arguments['realCrop'] = true;
	    }

	    try {
		    parent::setArguments(array(
			    'src' => $src,
			    'treatIdAsReference' => $treatIdAsReference,
			    'image' => $image
		    ));
		    parent::render();
	    } catch (Exception $ex) {
		    return 'Missing image!';
	    }

	    if ($this->arguments['realCrop']) {
		    return $this->tag->render();
	    }

	    // Ratio calculation
	    if (null !== $internalImage) {
		    $focusPointY = $internalImage->getProperty('focus_point_y');
		    $focusPointX = $internalImage->getProperty('focus_point_x');

		    $additionalClassDiv = 'focuspoint';
		    if (!empty($this->arguments['additionalClassDiv'])) {
			    $additionalClassDiv .= ' ' . $this->arguments['additionalClassDiv'];
		    }

		    $focusTag = '<div class="' . $additionalClassDiv . '" data-image-imageSrc="' . $this->tag->getAttribute('src') . '" data-focus-x="' . ($focusPointX / 100) . '" data-focus-y="' . ($focusPointY / 100) . '" data-image-w="' . $this->tag->getAttribute('width') . '" data-image-h="' . $this->tag->getAttribute('height') . '">';

		    return $focusTag . $this->tag->render() . '</div>';
	    } else {
		    return 'Missing internal image!';
	    }
    }
}