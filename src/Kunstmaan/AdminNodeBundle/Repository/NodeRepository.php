<?php

namespace Kunstmaan\AdminNodeBundle\Repository;

use Kunstmaan\AdminNodeBundle\Entity\Node;

use Kunstmaan\AdminBundle\Entity\PageIFace;

use Doctrine\ORM\EntityRepository;

/**
 * NodeRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class NodeRepository extends EntityRepository
{
	public function getTopNodes()
	{
	    $qb = $this->createQueryBuilder('b')
	               ->select('b')
                   ->where('b.parent is null')
	               ->addOrderBy('b.sequencenumber', 'DESC');

	    return $qb->getQuery()
	              ->getResult();
	}
	
	public function getChildren(Node $node){
		return $this->findBy(array("parent"=>$node->getId()));
	}
	
	public function getNodeFor(PageIFace $page) {
		return $this->findOneBy(array('refId' => $page->getId(), 'refEntityname' => get_class($page)));
	}
	
	public function getNodeForSlug($parentNode, $slug){
		$slugparts = explode("/", $slug);
		$result = null;
		foreach($slugparts as $slugpart){
			if($parentNode){
				//$result = $this->findOneBy(array('slug' => $slugpart, 'parent' => $parentNode->getId())) or $result;
				if($r = $this->findOneBy(array('slug' => $slugpart, 'parent' => $parentNode->getId()))){
					$result = $r;
				}
			} else {
				//$result = $this->findOneBy(array('slug' => $slugpart)) or $result;
				if($r = $this->findOneBy(array('slug' => $slugpart))){
					$result = $r;
				}
			}
		}
		return $result;
	}
}