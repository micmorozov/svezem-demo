#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 29 - Перевозка комбайнов

/*
перевезти 2(два) комбайна
*/

Transp -> AnyWord<kwtype="перевезти">;
What -> "комбайн";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_29);