<?php

namespace Repositories;

use Doctrine\ORM\EntityRepository;

/**
 * Infrastructure
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class Infrastructure extends EntityRepository
{
    /**
     * The cache key for the primary infrastructre (append IXP id)
     * @var string The cache key for the primary infrastructre (append IXP id)
     */
    const CACHE_KEY_PRIMARY = 'infrastructure_primary_';
    
    /**
     * The cache key for the all infrastructres (append IXP id)
     * @var string The cache key for all infrastructres (append IXP id)
     */
    const CACHE_KEY_ALL = 'infrastructure_all_';
    
    /**
     * Return an array of infrastructure names where the array key is the infrastructure id.
     *
     * @param \Entities\IXP $ixp IXP to filter infrastructure names.
     * @return array An array of infrastructure names with the infrastructure id as the key.
     */
    public function getNames( $ixp = false )
    {
        $dql = "SELECT i.id AS id, i.name AS name FROM Entities\\Infrastructure i";

        if( $ixp )
            $dql .= " WHERE i.IXP = ?1";

        $dql .= " ORDER BY name ASC";
        
        $query = $this->getEntityManager()->createQuery( $dql );

        if( $ixp )
            $query->setParameter( 1, $ixp );

        $ainfras = $query->getResult();
        
        $infras = [];
        foreach( $ainfras as $a )
            $infras[ $a['id'] ] = $a['name'];
        
        return $infras;
    }
    
    /**
     * Return the primary infrastructure (for a given IXP, or the default IXP)
     *
     * @throws \IXP_Exception
     * @param \Entities\IXP $ixp The IXP to find the primary infrastucture for. If null, uses the default IXP with ID 1.
     * @param bool $throw If true (default) throw an excpetion on database inconsistency (no primary, more that one priamry)
     * @return \Entities\Infrastructure The primary infrastructure for a given IXP. Or, if `$throw` is false, return false if no primary.
     */
    public function getPrimary( $ixp = null, $throw = true )
    {
        if( $ixp == null )
            $ixp = $this->getEntityManager()->getRepository( '\\Entities\\IXP' )->getDefault();
        
        $infra = $this->getEntityManager()->createQuery(
                "SELECT i
                    FROM Entities\\Infrastructure i
                    JOIN i.IXP ixp
                    WHERE i.isPrimary = 1
                        AND ixp = :ixp"
            )
            ->setParameter( 'ixp', $ixp )
            ->useResultCache( true, 7200, self::CACHE_KEY_PRIMARY . $ixp->getId() )
            ->getResult();
        
        if( !$infra || count( $infra ) > 1 )
        {
            // uh oh, inconsistency
            if( !$throw )
                return false;
            
            throw new \IXP_Exception(
                'When seeking the primary infrastructure of IXP ID #' . $ixp->getId() . ' we found none or more than one.'
                    . ' There must be one (and only one) infrastructure marked as private per IXP'
            );
        }
        
        return $infra[0];
    }
    
    /**
     * Get all infrastructures for an IXP
     *
     * @param \Entities\IXP $ixp The IXP to find the infrastuctures for
     * @return \Entities\Infrastructure[] The infrastructures for a given IXP
     */
    public function getAll( $ixp = null )
    {
        if( $ixp == null )
            $ixp = $this->getEntityManager()->getRepository( '\\Entities\\IXP' )->getDefault();
                            
        $infras = $this->getEntityManager()->createQuery(
                "SELECT i
                    FROM Entities\\Infrastructure i
                    JOIN i.IXP ixp
                    WHERE ixp = :ixp"
            )
            ->setParameter( 'ixp', $ixp )
            ->useResultCache( true, 7200, self::CACHE_KEY_ALL . $ixp->getId() )
            ->getResult();

        return $infras;
    }
    
    
    /**
     * Get all infrastructures for an IXP as an array indexed by their ids
     *
     * @param string $key The property of the infrastructure to place in the array (e.g. `Name`, `Shortname`)
     * @param \Entities\IXP $ixp The IXP to find the infrastuctures for
     * @return array The infrastructures for a given IXP
     */
    public function getAllAsArray( $key = 'Name', $ixp = null )
    {
        $infras = [];
        $fn = "get{$key}";
        
        $oInfras = $this->getAll( $ixp );

        foreach( $oInfras as $i )
            $infras[ $i->getId() ] = $i->$fn();

        return $infras;
    }

}
