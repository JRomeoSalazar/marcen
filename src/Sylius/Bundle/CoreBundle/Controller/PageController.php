<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\CoreBundle\Controller;

use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PageController extends ResourceController
{
	/**
     * @param Request $request
     * @param integer $id
     *
     * @return Response
     *
     * @throws NotFoundHttpException
     */
    public function showPageAction(Request $request)
    {
        // Obtenemos el 'id' de la página 
    	$id = $request->get('id');
    	
        // Si el 'id' no contiene '/indice' mostramos la página.
        if (strpos($id,'/indice') === false) {
            return $this->showAction($request);
        }
        // Si contiene /indice mostramos los hijos de la página
        else {
            $id = str_replace('/indice', '', $id);
            $children = $this->get('sylius.repository.page')->findChildren($id);
            return $this->render('SyliusWebBundle:Frontend/Page:childrenIndex.html.twig', array('pages' => $children));
        }
    }
}
