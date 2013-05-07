<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

$mapping_root = PKGPATH.'doctrine/vendor/Doctrine/ORM/Mapping/';

require_once $mapping_root.'Annotation.php';
require_once $mapping_root.'Entity.php';
require_once $mapping_root.'MappedSuperclass.php';
require_once $mapping_root.'InheritanceType.php';
require_once $mapping_root.'DiscriminatorColumn.php';
require_once $mapping_root.'DiscriminatorMap.php';
require_once $mapping_root.'Id.php';
require_once $mapping_root.'GeneratedValue.php';
require_once $mapping_root.'Version.php';
require_once $mapping_root.'JoinColumn.php';
require_once $mapping_root.'JoinColumns.php';
require_once CMFPATH.'classes/Doctrine/Mapping/Column.php';
require_once $mapping_root.'OneToOne.php';
require_once $mapping_root.'OneToMany.php';
require_once $mapping_root.'ManyToOne.php';
require_once $mapping_root.'ManyToMany.php';
require_once $mapping_root.'ElementCollection.php';
require_once $mapping_root.'Table.php';
require_once $mapping_root.'UniqueConstraint.php';
require_once $mapping_root.'Index.php';
require_once $mapping_root.'JoinTable.php';
require_once $mapping_root.'SequenceGenerator.php';
require_once $mapping_root.'CustomIdGenerator.php';
require_once $mapping_root.'ChangeTrackingPolicy.php';
require_once $mapping_root.'OrderBy.php';
require_once $mapping_root.'NamedQueries.php';
require_once $mapping_root.'NamedQuery.php';
require_once $mapping_root.'HasLifecycleCallbacks.php';
require_once $mapping_root.'PrePersist.php';
require_once $mapping_root.'PostPersist.php';
require_once $mapping_root.'PreUpdate.php';
require_once $mapping_root.'PostUpdate.php';
require_once $mapping_root.'PreRemove.php';
require_once $mapping_root.'PostRemove.php';
require_once $mapping_root.'PostLoad.php';
require_once $mapping_root.'PreFlush.php';
require_once $mapping_root.'FieldResult.php';
require_once $mapping_root.'ColumnResult.php';
require_once $mapping_root.'EntityResult.php';
require_once $mapping_root.'NamedNativeQuery.php';
require_once $mapping_root.'NamedNativeQueries.php';
require_once $mapping_root.'SqlResultSetMapping.php';
require_once $mapping_root.'SqlResultSetMappings.php';
require_once $mapping_root.'AssociationOverride.php';
require_once $mapping_root.'AssociationOverrides.php';
require_once $mapping_root.'AttributeOverride.php';
require_once $mapping_root.'AttributeOverrides.php';
