<?php


namespace KimaiPlugin\SharedProjectTimesheetsBundle\Repository;


use App\Entity\Project;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use KimaiPlugin\SharedProjectTimesheetsBundle\Entity\SharedProjectTimesheet;

class SharedProjectTimesheetRepository extends EntityRepository
{

    /**
     * @return SharedProjectTimesheet[]
     */
    public function findAll()
    {
        return $this->createQueryBuilder('spt')
            ->join(Project::class, 'p', Join::WITH, 'spt.project = p')
            ->orderBy('p.name, spt.shareKey', 'ASC')
            ->getQuery()
            ->execute();
    }

    /**
     * @param SharedProjectTimesheet $sharedProject
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(SharedProjectTimesheet $sharedProject)
    {
        $em = $this->getEntityManager();
        $em->persist($sharedProject);
        $em->flush();
    }

    /**
     * @param SharedProjectTimesheet $sharedProject
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function remove(SharedProjectTimesheet $sharedProject)
    {
        $em = $this->getEntityManager();
        $em->remove($sharedProject);
        $em->flush();
    }

    /**
     * @param Project|int|null $project
     * @param string|null $shareKey
     * @return SharedProjectTimesheet|null
     */
    public function findByProjectAndShareKey($project, ?string $shareKey)
    {
        try {
            return $this->createQueryBuilder('spt')
                ->where('spt.project = :project')
                ->andWhere('spt.shareKey = :shareKey')
                ->setMaxResults(1)
                ->setParameter('project', $project)
                ->setParameter('shareKey', $shareKey)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            // We can ignore that as we have a unique database key for project and shareKey
            return null;
        }
    }

}
