#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 1 - Перевозка животных

//Transp -> AnyWord<kwtype="перевезти">;
Animal -> AnyWord<kwtype="животные">;

Extra -> 'голова';

Fact -> Animal|Extra;

S -> Fact interp(Category.Category_1);
