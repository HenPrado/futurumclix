<?php
/**
 * Copyright (c) 2018 FuturumClix
 *
 * This program is free software: you can redistribute it and/or  modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Please notice this program incorporates variety of libraries or other
 * programs that may or may not have their own licenses, also they may or
 * may not be modified by FuturumClix. All modifications made by
 * FuturumClix are available under the terms of GNU Affero General Public
 * License, version 3, if original license allows that.
 */
/**
 * @copyright   2006-2013, Miles Johnson - http://milesj.me
 * @license     https://github.com/milesj/admin/blob/master/license.md
 * @link        http://milesj.me/code/cakephp/admin
 */

App::uses('Forum', 'Forum.Model');
App::uses('Topic', 'Forum.Model');
App::uses('Report', 'Forum.Model');

class ForumHelper extends AppHelper {

    /**
     * Helpers.
     *
     * @type array
     */
    public $helpers = array('Html', 'Session', 'Utility.Decoda', 'Utility.Utility');

    /**
     * Output a users avatar.
     *
     * @param array $user
     * @param int $size
     * @return string
     */
    public function avatar($user, $size = 100) {
        $userMap = Configure::read('User.fieldMap');
        $avatar = null;

        if (!empty($userMap['avatar']) && !empty($user['User'][$userMap['avatar']])) {
            $avatar = $this->Html->image($user['User'][$userMap['avatar']], array('width' => $size, 'height' => $size));

        } else if (Configure::read('Forum.settings.enableGravatar')) {
            $avatar = $this->Utility->gravatar($user['User'][$userMap['email']], array('size' => $size));
        }

        if ($avatar) {
            return $this->Html->div('avatar', $avatar);
        }

        return $avatar;
    }

    /**
     * Determine the forum icon state.
     *
     * @param array $forum
     * @param array $options
     * @return string
     */
    public function forumIcon($forum, array $options = array()) {
        $options = $options + array(
            'open' => 'mdi mdi-folder-open',
            'closed' => 'fa fa-lock',
            'new' => 'mdi mdi-folder-open'
        );
        $icon = 'open';
        $tooltip = '';

        if (isset($forum['LastPost']['created'])) {
            $lastPost = $forum['LastPost']['created'];

        } else if (isset($forum['LastTopic']['created'])) {
            $lastPost = $forum['LastTopic']['created'];
        }

        if ($forum['status'] == Forum::CLOSED) {
            $icon = 'closed';

        } else if (isset($lastPost) && $lastPost > $this->Session->read('Forum.lastVisit')) {
            $icon = 'new';
        }

        $custom = null;

        if (isset($forum['Forum']['icon'])) {
            $custom = $forum['Forum']['icon'];
        } else if (isset($forum['icon'])) {
            $custom = $forum['icon'];
        }

        if ($custom) {
            $custom = 'forum-icons'.DS.$custom;
            return $this->Html->image($custom);
        }

        switch ($icon) {
            case 'open': $tooltip = __d('forum', 'No New Posts'); break;
            case 'closed': $tooltip = __d('forum', 'Closed'); break;
            case 'new': $tooltip = __d('forum', 'New Posts'); break;
        }

        return $this->Html->tag('span', '', array('class' => $options[$icon] . ' js-tooltip', 'data-original-title' => $tooltip, 'data-placement' => 'top', 'data-toggle' => 'tooltip'));
    }

    /**
     * Get topics made in the past hour.
     *
     * @return int
     */
    public function getTopicsMade() {
        $pastHour = strtotime('-1 hour');
        $count = 0;

        if ($topics = $this->Session->read('Forum.topics')) {
            foreach ($topics as $time) {
                if ($time >= $pastHour) {
                    ++$count;
                }
            }
        }

        return $count;
    }

    /**
     * Get posts made in the past hour.
     *
     * @return int
     */
    public function getPostsMade() {
        $pastHour = strtotime('-1 hour');
        $count = 0;

        if ($posts = $this->Session->read('Forum.posts')) {
            foreach ($posts as $time) {
                if ($time >= $pastHour) {
                    ++$count;
                }
            }
        }

        return $count;
    }

    /**
     * Checks to see if the user has mod status.
     *
     * @param string $model
     * @param string $action
     * @param int $role
     * @return bool
     */
    public function hasAccess($model, $action, $role = null) {
        $user = $this->Session->read('Auth.User');

        if (empty($user)) {
            return false;
        } else if($user[Configure::read('User.fieldMap.status')] == Configure::read('User.statusMap.banned')) {
            return false;
        } else if ($this->isSuper()) {
            return true;

        } else if ($role !== null) {
            if (!$this->hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Return true if the user is a forum mod.
     *
     * @param int $forum_id
     * @return bool
     */
    public function isMod($forum_id) {
        return ($this->isSuper() || in_array($forum_id, (array) $this->Session->read('Forum.moderates')));
    }

    /**
     * Return a user profile URL.
     *
     * @param array $user
     * @return string
     */
    public function profileUrl($user) {
        return array('controller' => 'users', 'action' => 'profile', $user['username']);
    }

    /**
     * Get the users timezone.
     *
     * @return string
     */
    public function timezone() {
        if ($timezone = $this->Session->read(AuthComponent::$sessionKey . '.' . Configure::read('User.fieldMap.timezone'))) {
            return $timezone;
        }

        return Configure::read('Forum.settings.defaultTimezone');
    }

    /**
     * Determine the topic icon state.
     *
     * @param array $topic
     * @param array $options
     * @return string
     */
    public function topicIcon($topic, array $options = array()) {
        $options = $options + array(
            'open' => 'fa fa-comment-o',
            'open-hot' => 'fa fa-comments-o',
            'closed' => 'fa fa-lock',
            'new' => 'fa fa-comment',
            'new-hot' => 'fa fa-comments',
            'sticky' => 'fa fa-question-circle',
            'important' => 'fa fa-exclamation-circle',
            'announcement' => 'fa fa-warning'
        );

        $lastVisit = $this->Session->read('Forum.lastVisit');
        $readTopics = $this->Session->read('Forum.readTopics');

        if (!is_array($readTopics)) {
            $readTopics = array();
        }

        $icon = 'open';
        $tooltip = '';

        if (isset($topic['LastPost']['created'])) {
            $lastPost = $topic['LastPost']['created'];
        } else if (isset($topic['Topic']['created'])) {
            $lastPost = $topic['Topic']['created'];
        }

        if (!$topic['Topic']['status'] && $topic['Topic']['type'] != Topic::ANNOUNCEMENT) {
            $icon = 'closed';
        } else {
            if (isset($lastPost) && $lastPost > $lastVisit &&  !in_array($topic['Topic']['id'], $readTopics)) {
                $icon = 'new';
            } else if ($topic['Topic']['type'] == Topic::STICKY) {
                $icon = 'sticky';
            } else if ($topic['Topic']['type'] == Topic::IMPORTANT) {
                $icon = 'important';
            } else if ($topic['Topic']['type'] == Topic::ANNOUNCEMENT) {
                $icon = 'announcement';
            }
        }

        if ($icon === 'open' || $icon === 'new') {
            if ($topic['Topic']['post_count'] >= Configure::read('Forum.settings.postsTillHotTopic')) {
                $icon .= '-hot';
            }
        }

        switch ($icon) {
            case 'open': $tooltip = __d('forum', 'No New Posts'); break;
            case 'open-hot': $tooltip = __d('forum', 'No New Posts'); break;
            case 'closed': $tooltip = __d('forum', 'Closed'); break;
            case 'new': $tooltip = __d('forum', 'New Posts'); break;
            case 'new-hot': $tooltip = __d('forum', 'New Posts'); break;
            case 'sticky': $tooltip = __d('forum', 'Sticky'); break;
            case 'important': $tooltip = __d('forum', 'Important'); break;
            case 'announcement': $tooltip = __d('forum', 'Announcement'); break;
        }

        return $this->Html->tag('span', '', array('class' => $options[$icon] . ' js-tooltip', 'data-original-title' => $tooltip, 'data-placement' => 'top', 'data-toggle' => 'tooltip'));
    }

    /**
     * Get the amount of pages for a topic.
     *
     * @param array $topic
     * @return array
     */
    public function topicPages($topic) {
        if (empty($topic['page_count'])) {
            $postsPerPage = Configure::read('Forum.settings.postsPerPage');
            $topic['page_count'] = ($topic['post_count'] > $postsPerPage) ? ceil($topic['post_count'] / $postsPerPage) : 1;
        }

        $topicPages = array();

        for ($i = 1; $i <= $topic['page_count']; ++$i) {
            $topicPages[] = $this->Html->link($i, array('controller' => 'topics', 'action' => 'view', $topic['slug'], 'page' => $i));
        }

        if ($topic['page_count'] > Configure::read('Forum.settings.topicPagesTillTruncate')) {
            array_splice($topicPages, 2, $topic['page_count'] - 4, '...');
        }

        return $topicPages;
    }

    /**
     * Get the type of topic.
     *
     * @param int $type
     * @return string
     */
    public function topicType($type = null) {
        if (!$type) {
            return null;
        }

        return '<b>' . $this->Utility->enum('Forum.Topic', 'type', $type) . '</b>';
    }

    /**
     * Modify Decoda before rendering the view.
     *
     * @param string $viewFile
     */
    public function beforeRender($viewFile) {
        $censored = Configure::read('Forum.settings.censoredWords');

        if (is_string($censored)) {
            $censored = array_map('trim', explode(',', $censored));
        }

        $decoda = $this->Decoda->getDecoda();
        $decoda->addFilter(new \Decoda\Filter\BlockFilter(array(
            'spoilerToggle' => "$('#spoiler-content-{id}').toggle();"
        )));

        if ($censored) {
            $decoda->getHook('Censor')->blacklist($censored);
        }
    }

    /* from Admin.AdminHelper */

    /**
     * Check to see if the user has a role.
     *
     * @param int|string $role
     * @return bool
     */
    public function hasRole($role) {
        $roles = (array) $this->Session->read('Acl.roles');

        if (is_numeric($role)) {
            return isset($roles[$role]);
        }

        return in_array($role, $roles);
    }

    /**
     * Return true if the user is an admin.
     *
     * @return bool
     */
    public function isAdmin() {
        return (bool) $this->isSuper();
    }

    /**
     * Return true if the user is a super mod.
     *
     * @return bool
     */
    public function isSuper() {
        return ($this->Session->read('Acl.isSuper'));
    }

    public function getCountryFlag($location) {
        $location = explode('/', $location);

        if(is_array($location)) {
            $location = $location[0];
        }

        $res = ClassRegistry::init('Ip2NationCountry')->find('first', array(
            'fields' => array('code'),
            'conditions' => array('country' => $location),
        ));

        if(empty($res)) {
           if(Module::active('AccurateLocationDatabase')) {
               $res = ClassRegistry::init('AccurateLocationDatabase.AccurateLocationDatabaseIp')->find('first', array(
                  'fields' => array('code'),
                  'conditions' => array('country' => $location),
               ));
           }
           if(empty($res)) {
              return '';
           }
           $code = $res['AccurateLocationDatabaseIp']['code'];
        } else {
           $code = $res['Ip2NationCountry']['code'];
        }

        return $this->Html->image('flags'.DS.$code.'.png', array('class' => 'countryFlag js-tooltip', 'alt' => $location, 'data-original-title' => $location));
    }

}
