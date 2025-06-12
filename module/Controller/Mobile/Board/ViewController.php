<?php
/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall5 to newer
 * versions in the future.
 *
 * @copyright ⓒ 2016, NHN godo: Corp.
 * @link http://www.godo.co.kr
 */

namespace Controller\Mobile\Board;

use Component\Goods\GoodsCate;
use Component\Page\Page;
use Framework\Debug\Exception\AlertBackException;
use Framework\Debug\Exception\AlertCloseException;
use Framework\Debug\Exception\AlertRedirectException;
use Framework\Debug\Exception\RedirectLoginException;
use Framework\Debug\Exception\RequiredLoginException;
use Request;
use View\Template;
use Component\Validator\Validator;
use Globals;
use Component\Board\BoardView;
use Component\Board\BoardList;
use Session;

class ViewController extends \Bundle\Controller\Mobile\Board\ViewController
{
	public function pre()
    {
		## designpix.kkamu 20220211.s		
		$req = array_merge((array)Request::get()->toArray(), (array)Request::post()->toArray());

		$req = gd_htmlspecialchars($req);

		$extra = \App::load('\\Component\\Designpix\\ExtraBoard');

		$extraData = $extra->getExtra($req) ;

		if($extraData['useExtraEventFl']=='y'){
			$this->redirect('../event/free_event.php?sno='.$req['sno']);
			exit;
		}
		## designpix.kkamu 20220211.e	
		$this->setData('bdReq', $req);
	}

    public function index()
    {
		parent::index();
    }
}

