#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 51 - Перевозка гаражей

/*
Перевезти 2|два гаража
*/

What -> "гараж" | "бокс";

Fact -> (AnyWord+) What;

S -> Fact interp(Category.Category_51);
