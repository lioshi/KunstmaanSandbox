<?php

namespace Kunstmaan\ViewBundle\Controller;

use Kunstmaan\AdminBundle\Entity\PageIFace;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SlugController extends Controller
{
    /**
     * @Route("/{url}")
     * @Template()
     */
    public function slugAction($url)
    {
    	$em = $this->getDoctrine()->getEntityManager();
    	
    	//convert url to slug parts
    	$slugparts = explode("/", $url);
    	$page = null;

    	//lookup page by node slug
    	if(1!=1){
    		/*foreach ($slugparts as $slugpart ){
    		 $node = $em->getRepository('KunstmaanAdminNodeBundle:Node')->getNodeForSlug($page, $slugpart);
    		if($node){
    		$page = $node->getPage($em);
    		}
    		}*/
    	} else {
    		$node = $em->getRepository('KunstmaanAdminNodeBundle:Node')->getNodeForSlug(null, $url);
    		if($node){
    			$page = $node->getRef($em);
    		} else {
    			throw $this->createNotFoundException('No page found for slug ' . $url);
    		}
    	}

        //check if the requested node is online, else throw a 404 exception
        if(!$node->isOnline()){
            throw new NotFoundHttpException();
        }

        $nodeRoles = $node->getRoles();
        $currentUser = $this->container->get('security.context')->getToken()->getUser();

        $canViewPage = true;
        if(count($nodeRoles) > 0) {
            $canViewPage = false; //there are roles, so not viewable unless specifically allowed
            foreach($nodeRoles as $nodeRole){
                if($currentUser->hasRole($nodeRole)){
                    $canViewPage = true;
                    break;
                }
            }
        }

        if($canViewPage) {
        	//render page
        	$pageparts = $em->getRepository('KunstmaanPagePartBundle:PagePartRef')->getPageParts($em, $page);
            return array(
                    'page' => $page,
                    'pageparts' => $pageparts
            );
        }
        throw new createNotFoundException('You do not have suffucient rights to access this page.');
    }
}
