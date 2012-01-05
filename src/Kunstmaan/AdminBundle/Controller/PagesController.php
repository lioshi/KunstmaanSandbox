<?php

namespace Kunstmaan\AdminBundle\Controller;

use Kunstmaan\AdminNodeBundle\Modules\NodeMenu;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Kunstmaan\AdminBundle\Form\PageAdminType;
use Kunstmaan\AdminBundle\Entity\PageIFace;
use Kunstmaan\DemoBundle\AdminList\PageAdminListConfigurator;
use Kunstmaan\DemoBundle\PagePartAdmin\PagePartAdminConfigurator;
use Kunstmaan\PagePartBundle\Form\TextPagePartAdminType;
use Kunstmaan\AdminBundle\Form\NodeInfoAdminType;
use Kunstmaan\AdminBundle\Modules\ClassLookup;

class PagesController extends Controller
{
    
    public function indexAction()
    {
        $em = $this->getDoctrine()->getEntityManager();

        $user = $this->container->get('security.context')->getToken()->getUser();
        $topnodes = $em->getRepository('KunstmaanAdminNodeBundle:Node')->getTopNodes($user, 'write');
        $nodeMenu = new NodeMenu($this->container, null);

        $request    = $this->getRequest();
        $adminlist  = $this->get("adminlist.factory")->createList(new PageAdminListConfigurator($user, 'write'), $em);
        $adminlist->bindRequest($request);

        return $this->render('KunstmaanAdminBundle:Pages:index.html.twig', array(
			'topnodes'      => $topnodes,
        	'nodemenu' 	    => $nodeMenu,
            'pageadminlist' => $adminlist,
        ));
    }

    public function editAction($id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $request = $this->getRequest();
        
        $node = $em->getRepository('KunstmaanAdminNodeBundle:Node')->find($id);
        $page = $em->getRepository($node->getRefEntityname())->find($node->getRefId());
        
        $addpage = $request->get("addpage");
        $addpagetitle = $request->get("title");
        if(is_string($addpage) && $addpage != ''){
        	$newpage = new $addpage();
        	$newpage->setTitle('New page');
        	if(is_string($addpagetitle) && $addpagetitle != ''){
        		$newpage->setTitle($addpagetitle);
        	}
        	$newpage->setTranslatableLocale('en');
        	$em->persist($newpage);
        	$em->flush();
        	$nodeparent = $em->getRepository('KunstmaanAdminNodeBundle:Node')->getNodeFor($page);
        	$nodenewpage = $em->getRepository('KunstmaanAdminNodeBundle:Node')->getNodeFor($newpage);
        	$nodenewpage->setParent($nodeparent);
        	$em->persist($nodenewpage);
        	$em->flush();
        	return $this->redirect($this->generateUrl("KunstmaanAdminBundle_pages_edit", array('id'=>$nodenewpage->getId())));
        }
        
        $delete = $request->get("delete");
        if(is_string($delete) && $delete == 'true'){
        	//remove node and page
        	$nodeparent = $node->getParent();
        	$em->remove($page);
        	$em->flush();
        	return $this->redirect($this->generateUrl("KunstmaanAdminBundle_pages_edit", array('id'=>$nodeparent->getId())));
        }

		$page = $em->getRepository(ClassLookup::getClass($page))->find($id);  //'KunstmaanAdminBundle:Page'
        $locale = $request->getSession()->getLocale();
        $page->setTranslatableLocale($locale);
        $em->refresh($page);
        $repo = $em->getRepository('StofDoctrineExtensionsBundle:LogEntry');
        $logs = $repo->getLogEntries($page);
        if(!is_null($this->getRequest()->get('version'))) {
        	$repo->revert($page, $this->getRequest()->get('version'));
        }

        $user = $this->container->get('security.context')->getToken()->getUser();
        $topnodes   = $em->getRepository('KunstmaanAdminNodeBundle:Node')->getTopNodes($user, 'write');
        $node       = $em->getRepository('KunstmaanAdminNodeBundle:Node')->getNodeFor($page);

        $formfactory = $this->container->get('form.factory');
        $formbuilder = $this->createFormBuilder();

        //add the specific data from the custom page
        $formbuilder->add('main', $page->getDefaultAdminType());
        $formbuilder->add('node', $node->getDefaultAdminType($this->container));

        $formbuilder->setData(array('node' => $node, 'main' => $page));

        //handle the pagepart functions (fetching, change form to reflect all fields, assigning data, etc...)
        $pagepartadmin = $this->get("pagepartadmin.factory")->createList(new PagePartAdminConfigurator(), $em, $page);
        $pagepartadmin->preBindRequest($request);
        $pagepartadmin->adaptForm($formbuilder, $formfactory);

        if ($this->get('security.context')->isGranted('ROLE_PERMISSIONMANAGER')) {
            $permissionadmin = $this->get("kunstmaan_admin.permissionadmin");
            $permissionadmin->initialize($page, $em);
        }

        $form = $formbuilder->getForm();
        if ($request->getMethod() == 'POST') {
            $form           ->bindRequest($request);
            $pagepartadmin  ->bindRequest($request);

            if ($this->get('security.context')->isGranted('ROLE_PERMISSIONMANAGER')) {
                $permissionadmin->bindRequest($request);
            }

            if ($form->isValid()) {
                $em = $this->getDoctrine()->getEntityManager();

                $formValues = $request->request->get('form');
                if(isset($formValues['node']['roles'])) {
                    $roles = array_keys($formValues['node']['roles']);
                } else {
                    $roles = array();
                }

                $node->setRoles($roles);

                $em->persist($node);
                $em->persist($page);
                $em->flush();

                return $this->redirect($this->generateUrl('KunstmaanAdminBundle_pages_edit', array(
                    'id' => $node->getId()
                )));
            }
        }

        $nodeMenu = new NodeMenu($this->container, $node);

        $viewVariables = array(
            'topnodes'          => $topnodes,
            'page'              => $page,
            'entityname'        => ClassLookup::getClass($page),
            'form'              => $form->createView(),
            'pagepartadmin'     => $pagepartadmin,
            'logs'              => $logs,
            'nodemenu'          => $nodeMenu,
            'node'              => $node,
        );
        if($this->get('security.context')->isGranted('ROLE_PERMISSIONMANAGER')){
            $viewVariables['permissionadmin'] = $permissionadmin;
        }
        return $this->render('KunstmaanAdminBundle:Pages:edit.html.twig', $viewVariables);
    }
	
}
