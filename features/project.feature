Feature:  My user data

  @resetSchema
  @loadFixtures
  Scenario: As a user I should be able to get project list
    When I send a "GET" request to "/projects"
    Then the response status code should be 200
    And the response should be in JSON
    And the response should contain json:
    """
    {
       "@context": "\/contexts\/Project",
       "@id": "\/projects",
       "@type": "hydra:Collection",
       "hydra:member": [
           {
               "@id": "\/projects\/1",
               "@type": "Project",
               "id": 1,
               "number": "1",
               "name": "project 1",
               "budget": "1000.00",
               "status": "new",
               "users": [
                   "\/users\/2",
                   "\/users\/3",
                   "\/users\/4",
                   "\/users\/5",
                   "\/users\/6"
               ]
           },
            @...@
        ],
        "hydra:totalItems": 60,
         "hydra:view": {
             "@id": "\/projects?page=1",
             "@type": "hydra:PartialCollectionView",
             "hydra:first": "\/projects?page=1",
             "hydra:last": "\/projects?page=2",
             "hydra:next": "\/projects?page=2"
         }
    }
    """