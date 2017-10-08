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
        },
       "hydra:search": @array@
    }
    """

  @resetSchema
  @loadFixtures
  Scenario: As a user I should be able to get project list ordered by name

    When I send a "GET" request to "/projects?order[id]=desc"

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
               "@id": "\/projects\/60",
               "@type": "Project",
               "id": 60,
               "number": "60",
               "name": "project 60",
               "budget": "3000.00",
               "status": "new",
               "users": [
                   "\/users\/11",
                   "\/users\/12",
                   "\/users\/13",
                   "\/users\/14",
                   "\/users\/15"
               ]
           },
            @...@
       ],
       "hydra:totalItems": 60,
       "hydra:view": {
           "@id": "\/projects?order%5Bid%5D=desc&page=1",
           "@type": "hydra:PartialCollectionView",
           "hydra:first": "\/projects?order%5Bid%5D=desc&page=1",
           "hydra:last": "\/projects?order%5Bid%5D=desc&page=2",
           "hydra:next": "\/projects?order%5Bid%5D=desc&page=2"
       },
       "hydra:search": {
            "@type": "hydra:IriTemplate",
            "hydra:template": "\/projects{?order[id],order[name],order[number],order[budget]}",
            "hydra:variableRepresentation": "BasicRepresentation",
            "hydra:mapping": [
                {
                    "@type": "IriTemplateMapping",
                    "variable": "order[id]",
                    "property": "id",
                    "required": false
                },
                {
                    "@type": "IriTemplateMapping",
                    "variable": "order[name]",
                    "property": "name",
                    "required": false
                },
                {
                    "@type": "IriTemplateMapping",
                    "variable": "order[number]",
                    "property": "number",
                    "required": false
                },
                {
                    "@type": "IriTemplateMapping",
                    "variable": "order[budget]",
                    "property": "budget",
                    "required": false
                }
            ]
       }
    }
    """